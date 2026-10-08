<?php

namespace App\Http\Middleware;

use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->roleLabel(),
                    'is_admin' => $user->isAdmin(),
                    'is_supervisor' => $user->isSupervisor(),
                    'is_guard' => $user->isGuard(),
                    'initials' => strtoupper(substr($user->name, 0, 2)),
                ] : null,
            ],
            'unreadNotifications' => $user
                ? SystemNotification::where('user_id', $user->id)->whereNull('read_at')->count()
                : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appTimezone' => config('app.timezone'),
        ];
    }
}
