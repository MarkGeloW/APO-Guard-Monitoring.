<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function revokeAccess(): void
    {
        $this->tokens()->delete();
        $this->forceFill(['remember_token' => null])->saveQuietly();

        DB::table('sessions')->where('user_id', $this->id)->delete();
    }

    public function archive(): void
    {
        $this->forceFill(['is_active' => false])->saveQuietly();
        $this->revokeAccess();

        $personnel = $this->securityPersonnel;
        if ($personnel && ! $personnel->trashed()) {
            $personnel->forceFill(['status' => 'inactive'])->saveQuietly();
            $personnel->deployments()
                ->whereIn('status', ['scheduled', 'active'])
                ->update(['status' => 'cancelled']);
            $personnel->delete();
        }

        $this->delete();
    }

    public function restoreAccount(): void
    {
        $this->restore();
        $this->forceFill(['is_active' => true])->saveQuietly();

        $personnel = SecurityPersonnel::onlyTrashed()->where('user_id', $this->id)->first();
        if ($personnel) {
            $personnel->restore();
            $personnel->forceFill(['status' => 'active'])->saveQuietly();
        }
    }

    public function securityPersonnel(): HasOne
    {
        return $this->hasOne(SecurityPersonnel::class);
    }

    /**
     * Guard logins that still need a personnel profile (ignores soft-deleted links).
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function guardsWithoutPersonnelProfile()
    {
        $linkedIds = SecurityPersonnel::withTrashed()
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return static::query()
            ->where('role', UserRole::Guard)
            ->whereNotIn('id', $linkedIds);
    }

    /**
     * Create personnel rows for any Guard accounts that were saved without a profile.
     */
    public static function syncMissingGuardPersonnelProfiles(): int
    {
        $created = 0;

        static::guardsWithoutPersonnelProfile()
            ->orderBy('id')
            ->get()
            ->each(function (self $user) use (&$created) {
                $user->ensurePersonnelProfile();
                $created++;
            });

        return $created;
    }

    /**
     * Ensure this Guard account has an active personnel profile (used by Personnel / Deployment).
     *
     * @param  list<string>  $idPaths
     */
    public function ensurePersonnelProfile(?string $licenseNumber = null, array $idPaths = [], ?string $dateHired = null): SecurityPersonnel
    {
        $existing = SecurityPersonnel::withTrashed()->where('user_id', $this->id)->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->forceFill([
                'status' => 'active',
                'email' => $existing->email ?: $this->email,
                'phone' => $existing->phone ?: $this->phone,
            ])->saveQuietly();

            return $existing->refresh();
        }

        $nameParts = preg_split('/\s+/', trim($this->name), 2) ?: ['Guard', ''];
        $license = $licenseNumber ?: ('TEMP-'.$this->id);

        while (SecurityPersonnel::withTrashed()->where('license_number', $license)->exists()) {
            $license = 'TEMP-'.$this->id.'-'.Str::lower(Str::random(4));
        }

        return SecurityPersonnel::create([
            'user_id' => $this->id,
            'license_number' => $license,
            'id_picture_paths' => $idPaths !== [] ? $idPaths : null,
            'first_name' => $nameParts[0] ?: 'Guard',
            'last_name' => $nameParts[1] ?? '',
            'email' => $this->email,
            'phone' => $this->phone,
            'date_hired' => $dateHired ?? now()->toDateString(),
            'status' => 'active',
            'notes' => $licenseNumber ? null : 'Auto-linked from User Management. Update license number and ID pictures in Personnel.',
        ]);
    }

    public function assignedDeployments(): HasMany
    {
        return $this->hasMany(Deployment::class, 'assigned_by');
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isBreakGlassAdmin(): bool
    {
        $email = strtolower((string) config('apo.break_glass.email', 'emergency@aposecurity.com'));

        return strtolower((string) $this->email) === $email;
    }

    public function isLastActiveAdmin(): bool
    {
        if (! $this->isAdmin() || ! $this->is_active || $this->trashed()) {
            return false;
        }

        return static::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereKeyNot($this->id)
            ->doesntExist();
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::Supervisor;
    }

    public function isGuard(): bool
    {
        return $this->role === UserRole::Guard;
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }

    public function roleTableLabel(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'Administrator',
            UserRole::Supervisor => 'Supervisor',
            UserRole::Guard => 'Guard',
        };
    }

    public function userCode(): string
    {
        return 'U-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function username(): string
    {
        return Str::before($this->email, '@');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    public function joinedLabel(): string
    {
        return $this->created_at?->format('M Y') ?? '—';
    }

    public function roleBadgeClasses(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'bg-purple-50 text-purple-600 ring-purple-100',
            UserRole::Supervisor => 'bg-blue-50 text-blue-600 ring-blue-100',
            UserRole::Guard => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        };
    }

    public function avatarClasses(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'bg-purple-500',
            UserRole::Supervisor => 'bg-blue-500',
            UserRole::Guard => 'bg-emerald-500',
        };
    }
}
