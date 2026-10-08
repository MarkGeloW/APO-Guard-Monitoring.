<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Your account is inactive or unauthorized.'], 403);
            }

            abort(403, 'Your account is inactive or unauthorized.');
        }

        $allowedRoles = array_map(
            fn (string $role) => UserRole::from($role),
            $roles
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
            }

            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
