<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SecurityPersonnel extends Model
{
    protected $table = 'security_personnel';

    protected $fillable = [
        'user_id',
        'badge_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'date_hired',
        'status',
        'emergency_contact',
        'emergency_phone',
        'notes',
        'face_image_path',
        'face_signature',
        'face_enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'face_signature' => 'array',
            'face_enrolled_at' => 'datetime',
        ];
    }

    public function hasFaceEnrolled(): bool
    {
        return filled($this->face_image_path)
            && is_array($this->face_signature)
            && count($this->face_signature) >= 8
            && $this->face_enrolled_at !== null;
    }

    public function faceImageUrl(): ?string
    {
        return $this->face_image_path
            ? Storage::disk('public')->url($this->face_image_path)
            : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function guardCode(): string
    {
        return 'G-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function initials(): string
    {
        return strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1));
    }

    public function postsCount(): int
    {
        return $this->deployments_count ?? $this->deployments()->count();
    }

    public function rank(): string
    {
        $posts = $this->postsCount();

        if ($posts >= 6) {
            return 'Team Leader';
        }

        if ($posts >= 3) {
            return 'Senior Guard';
        }

        return 'Guard';
    }

    public function isProbation(): bool
    {
        if ($this->status === 'on_leave') {
            return true;
        }

        return $this->date_hired !== null && $this->date_hired->diffInDays(now()) < 90;
    }

    public function displayStatusLabel(): string
    {
        if ($this->isProbation()) {
            return 'Probation';
        }

        if ($this->status === 'active') {
            return 'Active';
        }

        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function zoneLabel(): string
    {
        $latestDeployment = $this->relationLoaded('deployments')
            ? $this->deployments->sortByDesc('deployment_date')->first()
            : $this->deployments()->latest('deployment_date')->first();

        if (! $latestDeployment?->location_id) {
            return 'Unassigned';
        }

        $zones = ['Zone A', 'Zone B', 'Zone C', 'Zone D'];

        return $zones[($latestDeployment->location_id - 1) % 4];
    }

    public function joinedLabel(): string
    {
        return $this->date_hired?->format('M Y') ?? '—';
    }
}
