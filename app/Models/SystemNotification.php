<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function severity(): string
    {
        return match ($this->type) {
            'attendance_absent', 'attendance_critical', 'duty_off_post', 'duty_signal_lost' => 'error',
            'attendance_late', 'gps_lost' => 'warning',
            'attendance_checkin' => 'success',
            'deployment_assigned', 'deployment_schedule', 'system_info', 'system_backup' => 'info',
            default => $this->isUnread() ? 'warning' : 'info',
        };
    }

    public function severityLabel(): string
    {
        return match ($this->severity()) {
            'error' => 'Error',
            'warning' => 'Warning',
            'success' => 'Success',
            default => 'Info',
        };
    }
}
