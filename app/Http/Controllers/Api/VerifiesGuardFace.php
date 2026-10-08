<?php

namespace App\Http\Controllers\Api;

use App\Services\FaceMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait VerifiesGuardFace
{
    protected function requireEnrolledFace($personnel): ?JsonResponse
    {
        if ($personnel->hasFaceEnrolled()) {
            return null;
        }

        return response()->json([
            'message' => 'Face enrollment is required before attendance. Open Face Scan to enroll first.',
            'face_enrolled' => false,
        ], 422);
    }

    /**
     * @return array{path: ?string, score: ?float, verified: bool}|JsonResponse
     */
    protected function verifyFaceCapture(Request $request, $personnel, FaceMatchingService $matcher, string $folder)
    {
        $validated = $request->validate([
            'face_image' => ['required', 'image', 'max:5120'],
            'face_signature' => ['required', 'string'],
        ]);

        $signature = json_decode($validated['face_signature'], true);
        $result = $matcher->compare($personnel->face_signature, is_array($signature) ? $signature : null);

        if (! $result['matched']) {
            return response()->json([
                'message' => $result['message'],
                'face_verified' => false,
                'face_score' => $result['score'],
            ], 422);
        }

        $path = $request->file('face_image')->store(
            'faces/attendance/'.$personnel->id.'/'.$folder,
            'public'
        );

        return [
            'path' => $path,
            'score' => $result['score'],
            'verified' => true,
        ];
    }

    protected function deleteFacePath(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
