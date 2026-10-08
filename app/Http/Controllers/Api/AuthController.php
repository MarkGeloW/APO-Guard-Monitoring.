<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive. Contact your supervisor.'],
            ]);
        }

        if (! $user->isGuard()) {
            throw ValidationException::withMessages([
                'email' => ['Only security guard accounts can use the mobile app.'],
            ]);
        }

        $user->tokens()->where('name', $validated['device_name'] ?? 'guard-mobile')->delete();

        $token = $user->createToken($validated['device_name'] ?? 'guard-mobile')->plainTextToken;
        $personnel = $user->securityPersonnel;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'initials' => $user->initials(),
                'guard_code' => $personnel?->guardCode(),
                'badge_number' => $personnel?->badge_number,
                'personnel_id' => $personnel?->id,
                'face_enrolled' => $personnel?->hasFaceEnrolled() ?? false,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('securityPersonnel');
        $personnel = $user->securityPersonnel;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'initials' => $user->initials(),
                'guard_code' => $personnel?->guardCode(),
                'badge_number' => $personnel?->badge_number,
                'personnel_id' => $personnel?->id,
                'face_enrolled' => $personnel?->hasFaceEnrolled() ?? false,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out successfully.']);
    }
}
