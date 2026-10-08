<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Services\DutyPresenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardDutyPingController extends Controller
{
    public function store(Request $request, DutyPresenceService $presence): JsonResponse
    {
        $validated = $request->validate([
            'pings' => ['required', 'array', 'min:1', 'max:20'],
            'pings.*.client_uuid' => ['required', 'uuid'],
            'pings.*.deployment_id' => ['required', 'integer'],
            'pings.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'pings.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'pings.*.captured_at' => ['required', 'date'],
        ]);

        $personnel = $request->user()->securityPersonnel;
        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        $saved = [];
        $skipped = [];

        foreach ($validated['pings'] as $ping) {
            $uuid = $ping['client_uuid'];
            $deployment = Deployment::with(['location', 'attendance', 'personnel'])
                ->where('id', $ping['deployment_id'])
                ->where('security_personnel_id', $personnel->id)
                ->first();

            $capturedAt = Carbon::parse($ping['captured_at']);

            if (! $deployment || ! $deployment->attendance?->time_in) {
                $skipped[] = ['client_uuid' => $uuid, 'reason' => 'not_on_duty'];
                continue;
            }

            if ($capturedAt->greaterThan(now()->addMinutes(2)) || $capturedAt->lessThan(now()->subDay())) {
                $skipped[] = ['client_uuid' => $uuid, 'reason' => 'invalid_time'];
                continue;
            }

            if ($deployment->attendance->time_out && $capturedAt->greaterThan($deployment->attendance->time_out)) {
                $skipped[] = ['client_uuid' => $uuid, 'reason' => 'after_checkout'];
                continue;
            }

            $record = $presence->record(
                $deployment,
                $uuid,
                (float) $ping['latitude'],
                (float) $ping['longitude'],
                $capturedAt,
            );

            $saved[] = [
                'client_uuid' => $record->client_uuid,
                'within_geofence' => $record->within_geofence,
                'distance_meters' => $record->distance_meters,
                'captured_at' => $record->captured_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'saved' => $saved,
            'skipped' => $skipped,
            'interval_minutes' => DutyPresenceService::INTERVAL_MINUTES,
        ]);
    }
}
