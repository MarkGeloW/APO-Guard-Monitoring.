<?php

namespace App\Services;

class GeolocationService
{
    public function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function isWithinGeofence(float $userLat, float $userLon, float $targetLat, float $targetLon, int $radiusMeters): bool
    {
        return $this->distanceInMeters($userLat, $userLon, $targetLat, $targetLon) <= $radiusMeters;
    }
}
