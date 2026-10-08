<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Services\DutyPresenceService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function index(): Response
    {
        $todayDeployments = Deployment::operationalToday();

        $mapData = $this->buildMapData($todayDeployments);
        $guards = collect($mapData['guards']);

        return Inertia::render('Monitoring/Index', [
            'mapData' => $mapData,
            'guards' => $guards,
        ]);
    }

    public function liveData(): JsonResponse
    {
        $todayDeployments = Deployment::operationalToday();

        $mapData = $this->buildMapData($todayDeployments);

        return response()->json([
            'guards' => $mapData['guards'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function buildMapData($todayDeployments): array
    {
        $zoneColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#ede9fe'];
        $zoneBorders = ['#93c5fd', '#86efac', '#fcd34d', '#c4b5fd'];

        $locations = Location::where('status', 'active')->get()->values()->map(function ($location, $index) use ($zoneColors, $zoneBorders) {
            $zoneIndex = $index % 4;

            return [
                'id' => $location->id,
                'name' => $location->name,
                'zone' => 'Zone '.chr(65 + $zoneIndex),
                'zoneIndex' => $zoneIndex,
                'fillColor' => $zoneColors[$zoneIndex],
                'borderColor' => $zoneBorders[$zoneIndex],
                'address' => $location->address,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'radius' => $location->geofence_radius,
            ];
        });

        $presence = app(DutyPresenceService::class);
        $latestPings = $presence->latestPingsFor($todayDeployments);

        $guards = $todayDeployments->map(function (Deployment $deployment) use ($presence, $latestPings) {
            $attendance = $deployment->attendance;
            $displayStatus = $deployment->displayStatus();
            $zoneIndex = ($deployment->location_id - 1) % 4;
            $lastPing = $latestPings->get($deployment->id);
            $signal = $presence->signalFor($deployment, $lastPing);

            $markerStatus = match ($displayStatus) {
                'active' => 'on_duty',
                'warning' => 'late',
                'completed' => 'off_duty',
                'pending' => 'off_duty',
                'absent' => 'absent',
                default => 'off_duty',
            };

            $lat = $lastPing?->latitude ?? $attendance?->time_in_latitude ?? $deployment->location->latitude;
            $lng = $lastPing?->longitude ?? $attendance?->time_in_longitude ?? $deployment->location->longitude;

            return [
                'id' => $deployment->id,
                'name' => $deployment->personnel->fullName(),
                'badge' => $deployment->personnel->badge_number,
                'guard_code' => $deployment->personnel->guardCode(),
                'location' => $deployment->location->name,
                'zone' => 'Zone '.chr(65 + $zoneIndex),
                'zone_index' => $zoneIndex,
                'status' => $attendance?->status ?? 'pending',
                'displayStatus' => $displayStatus,
                'markerStatus' => $markerStatus,
                'status_label' => match ($markerStatus) {
                    'on_duty' => 'On Duty',
                    'late' => 'Late',
                    'absent' => 'Absent',
                    default => 'Off Duty',
                },
                'lat' => $lat,
                'lng' => $lng,
                'time_in' => Attendance::formatDisplayTime($attendance?->time_in),
                'shift' => $deployment->shiftRange(),
                'shift_type' => $deployment->shiftType(),
                'signal_state' => $signal['state'],
                'signal_label' => $signal['label'],
                'signal_tone' => $signal['tone'],
                'signal_minutes' => $signal['minutes'],
            ];
        })->values();

        return [
            'locations' => $locations,
            'guards' => $guards,
        ];
    }
}
