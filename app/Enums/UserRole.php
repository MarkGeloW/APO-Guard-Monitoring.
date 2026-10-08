<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Guard = 'guard';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Supervisor => 'Supervisor',
            self::Guard => 'Security Guard',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isSupervisor(): bool
    {
        return $this === self::Supervisor;
    }

    public function isGuard(): bool
    {
        return $this === self::Guard;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function canManagePersonnel(): bool
    {
        return in_array($this, [self::Admin, self::Supervisor], true);
    }

    public function canManageDeployments(): bool
    {
        return in_array($this, [self::Admin, self::Supervisor], true);
    }

    public function canViewMonitoring(): bool
    {
        return in_array($this, [self::Admin, self::Supervisor], true);
    }
}
