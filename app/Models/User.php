<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function securityPersonnel(): HasOne
    {
        return $this->hasOne(SecurityPersonnel::class);
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
