<?php

namespace App\Http\Support;

use App\Models\Deployment;
use App\Models\SecurityPersonnel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InertiaPageData
{
    public static function deploymentRow(Deployment $deployment): array
    {
        return [
            'id' => $deployment->id,
            'deploy_code' => $deployment->deployCode(),
            'guard_name' => $deployment->personnel->fullName(),
            'location' => $deployment->location->name,
            'date' => $deployment->deployment_date->format('M j, Y'),
            'shift' => $deployment->shiftRange(),
            'display_status' => $deployment->displayStatus(),
            'display_status_label' => $deployment->displayStatusLabel(),
        ];
    }

    public static function paginatedDeployments(LengthAwarePaginator $paginator): array
    {
        return $paginator->through(fn (Deployment $d) => self::deploymentRow($d))->toArray();
    }

    public static function personnelCard(SecurityPersonnel $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->fullName(),
            'initials' => $person->initials(),
            'rank' => $person->rank(),
            'zone' => $person->zoneLabel(),
            'joined' => $person->joinedLabel(),
            'posts' => $person->postsCount(),
            'status_label' => $person->displayStatusLabel(),
            'is_probation' => $person->isProbation(),
            'face_enrolled' => $person->hasFaceEnrolled(),
            'face_image_url' => $person->faceImageUrl(),
        ];
    }

    public static function paginatedPersonnel(LengthAwarePaginator $paginator): array
    {
        return $paginator->through(fn (SecurityPersonnel $p) => self::personnelCard($p))->toArray();
    }

    public static function userRow(User $user): array
    {
        return [
            'id' => $user->id,
            'user_code' => $user->userCode(),
            'name' => $user->name,
            'username' => $user->username(),
            'initials' => $user->initials(),
            'role' => $user->role->value,
            'role_label' => $user->roleTableLabel(),
            'role_tone' => match ($user->role->value) {
                'admin' => 'administrator',
                'supervisor' => 'supervisor',
                default => 'guard',
            },
            'avatar_classes' => match ($user->role->value) {
                'admin' => 'bg-purple-500',
                'supervisor' => 'bg-blue-500',
                default => 'bg-emerald-500',
            },
            'is_active' => $user->is_active,
            'joined' => $user->joinedLabel(),
        ];
    }

    public static function serializeTimes(Collection $items): array
    {
        return $items->map(function ($item) {
            if (is_array($item) && isset($item['time']) && $item['time'] instanceof \Carbon\Carbon) {
                $item['time'] = $item['time']->toIso8601String();
            }

            return $item;
        })->values()->all();
    }
}
