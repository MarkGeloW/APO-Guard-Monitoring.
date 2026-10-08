<?php

namespace App\Services;

class FaceMatchingService
{
    /**
     * Maximum average absolute landmark error for a successful match.
     * Lower = stricter. Tuned for normalized [0..1] landmark vectors.
     */
    public const MATCH_THRESHOLD = 0.22;

    public function hasValidSignature(?array $signature): bool
    {
        if (! is_array($signature) || count($signature) < 8) {
            return false;
        }

        foreach ($signature as $value) {
            if (! is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    public function compare(?array $enrolled, ?array $candidate, ?float $threshold = null): array
    {
        $threshold ??= self::MATCH_THRESHOLD;

        if (! $this->hasValidSignature($enrolled) || ! $this->hasValidSignature($candidate)) {
            return [
                'matched' => false,
                'score' => null,
                'message' => 'Face signature is incomplete. Please scan again.',
            ];
        }

        $length = min(count($enrolled), count($candidate));
        if ($length < 8) {
            return [
                'matched' => false,
                'score' => null,
                'message' => 'Face could not be measured clearly. Hold still and try again.',
            ];
        }

        $total = 0.0;
        for ($i = 0; $i < $length; $i++) {
            $total += abs((float) $enrolled[$i] - (float) $candidate[$i]);
        }

        $score = $total / $length;
        $matched = $score <= $threshold;

        return [
            'matched' => $matched,
            'score' => round($score, 4),
            'message' => $matched
                ? 'Face verified successfully.'
                : 'Face did not match your enrolled profile. Try again in better lighting.',
        ];
    }
}
