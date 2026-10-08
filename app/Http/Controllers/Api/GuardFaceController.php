<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FaceMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuardFaceController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $personnel = $request->user()->securityPersonnel;

        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        return response()->json([
            'face_enrolled' => $personnel->hasFaceEnrolled(),
            'face_enrolled_at' => $personnel->face_enrolled_at?->toIso8601String(),
        ]);
    }

    public function enroll(Request $request, FaceMatchingService $matcher): JsonResponse
    {
        $personnel = $request->user()->securityPersonnel;

        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        $validated = $request->validate([
            'face_image' => ['required', 'image', 'max:5120'],
            'face_signature' => ['required', 'string'],
        ]);

        $signature = json_decode($validated['face_signature'], true);
        if (! $matcher->hasValidSignature($signature)) {
            return response()->json([
                'message' => 'Could not read your face clearly. Center your face and try again.',
            ], 422);
        }

        if ($personnel->face_image_path) {
            Storage::disk('public')->delete($personnel->face_image_path);
        }

        $path = $request->file('face_image')->store(
            'faces/personnel/'.$personnel->id,
            'public'
        );

        $personnel->update([
            'face_image_path' => $path,
            'face_signature' => array_map('floatval', $signature),
            'face_enrolled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Face enrolled successfully. It will be required for check-in and check-out.',
            'face_enrolled' => true,
            'face_enrolled_at' => $personnel->fresh()->face_enrolled_at?->toIso8601String(),
        ]);
    }
}
