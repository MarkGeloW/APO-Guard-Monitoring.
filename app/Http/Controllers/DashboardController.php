<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\Location;
use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $todayDeployments = Deployment::operationalToday();

        $yesterdayDeployments = Deployment::with('attendance')
            ->whereDate('deployment_date', today()->subDay())
            ->get();

        $lateToday = $todayDeployments->filter(fn ($d) => $d->attendance?->checkInLatenessMinutes($d));
        $absentToday = $todayDeployments->filter(fn ($d) => $d->attendanceTabStatus() === 'absent');
        $onDutyNow = $todayDeployments->filter(fn ($d) => $d->isOnDuty())->count();

        $onDutyYesterday = $yesterdayDeployments->filter(fn ($d) => $d->isOnDuty())->count();

        $latestLate = $lateToday->sortByDesc(fn ($d) => $d->attendance?->time_in)->first();
        $latestAbsent = $absentToday->first();

        $stats = [
            'on_duty' => $onDutyNow,
            'deployed_today' => $todayDeployments->count(),
            'on_duty_delta' => $onDutyNow - $onDutyYesterday,
            'late_count' => $lateToday->count(),
            'late_delta' => $lateToday->count() - $yesterdayDeployments->filter(
                fn ($d) => $d->attendance?->checkInLatenessMinutes($d)
            )->count(),
            'late_name' => $latestLate?->personnel?->fullName(),
            'late_location' => $latestLate?->location?->name,
            'absent_count' => $absentToday->count(),
            'absent_name' => $latestAbsent?->personnel?->fullName(),
            'active_posts' => $todayDeployments->pluck('location_id')->unique()->count(),
            'zones_today' => Location::where('status', 'active')->count(),
        ];

        $zoneColors = [
            ['fill' => '#dbeafe', 'border' => '#93c5fd'],
            ['fill' => '#dcfce7', 'border' => '#86efac'],
            ['fill' => '#fef3c7', 'border' => '#fcd34d'],
            ['fill' => '#ede9fe', 'border' => '#c4b5fd'],
        ];

        $mapData = [
            'locations' => Location::where('status', 'active')->get()->values()->map(function ($loc, $index) use ($zoneColors) {
                $zoneIndex = $index % 4;

                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'zone' => 'Zone '.chr(65 + $zoneIndex),
                    'zoneIndex' => $zoneIndex,
                    'fillColor' => $zoneColors[$zoneIndex]['fill'],
                    'borderColor' => $zoneColors[$zoneIndex]['border'],
                    'address' => $loc->address,
                    'lat' => $loc->latitude,
                    'lng' => $loc->longitude,
                    'radius' => $loc->geofence_radius,
                ];
            }),
            'guards' => $todayDeployments->map(function ($deployment) {
                $attendance = $deployment->attendance;
                $markerStatus = $deployment->attendanceTabStatus();
                $zoneIndex = max(0, ($deployment->location_id - 1) % 4);

                return [
                    'id' => $deployment->id,
                    'name' => $deployment->personnel->fullName(),
                    'location' => $deployment->location->name,
                    'zone' => 'Zone '.chr(65 + $zoneIndex),
                    'status' => $attendance?->status ?? 'pending',
                    'markerStatus' => $markerStatus,
                    'lat' => $attendance?->time_in_latitude ?? $deployment->location->latitude,
                    'lng' => $attendance?->time_in_longitude ?? $deployment->location->longitude,
                ];
            })->values(),
        ];

        $alerts = $this->buildAlerts($user->id, $todayDeployments);

        $weeklyAttendance = collect(range(6, 0))->map(function ($daysAgo) {
            $date = today()->subDays($daysAgo);

            $records = Attendance::with('deployment')
                ->whereHas('deployment', fn ($q) => $q->whereDate('deployment_date', $date))
                ->whereNotNull('time_in')
                ->get();

            return [
                'label' => $date->format('D'),
                'present' => $records->filter(fn (Attendance $a) => $a->checkInLatenessMinutes($a->deployment) === null)->count(),
                'late' => $records->filter(fn (Attendance $a) => $a->checkInLatenessMinutes($a->deployment) !== null)->count(),
            ];
        });

        $checkInTrend = $this->buildCheckInTrend($todayDeployments);

        $zoneCoverage = Location::where('status', 'active')
            ->get()
            ->values()
            ->map(function ($location, $index) use ($todayDeployments) {
                $zone = 'Zone '.chr(65 + ($index % 4));

                return [
                    'zone' => $zone,
                    'count' => $todayDeployments->where('location_id', $location->id)->count(),
                    'color' => ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'][$index % 4],
                ];
            })
            ->groupBy('zone')
            ->map(fn (Collection $items, string $zone) => [
                'zone' => $zone,
                'count' => $items->sum('count'),
                'color' => $items->first()['color'],
            ])
            ->values();

        return Inertia::render($user->isSupervisor() ? 'Supervisor/Dashboard' : 'Dashboard', [
            'stats' => $stats,
            'mapData' => $mapData,
            'alerts' => $alerts->map(function (array $alert) {
                $time = $alert['time'] instanceof \Carbon\Carbon
                    ? $alert['time']
                    : \Carbon\Carbon::parse($alert['time']);

                return [
                    'severity' => $alert['severity'],
                    'message' => $alert['message'],
                    'time' => $time->toIso8601String(),
                    'time_label' => $time->format('h:i A'),
                ];
            })->values(),
            'weeklyAttendance' => $weeklyAttendance->values(),
            'checkInTrend' => $checkInTrend,
            'zoneCoverage' => $zoneCoverage,
        ]);
    }

    private function buildAlerts(int $userId, Collection $todayDeployments): Collection
    {
        $alerts = SystemNotification::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                $severity = match ($notification->type) {
                    'attendance_checkin' => 'success',
                    'deployment_assigned', 'deployment_schedule', 'system_backup', 'system_info' => 'info',
                    'gps_lost', 'attendance_late' => 'warning',
                    'attendance_absent', 'attendance_critical' => 'critical',
                    default => $notification->isUnread() ? 'warning' : 'info',
                };

                return [
                    'severity' => $severity,
                    'message' => $notification->message,
                    'time' => $notification->created_at,
                ];
            });

        foreach ($todayDeployments as $deployment) {
            $name = $deployment->personnel->fullName();
            $location = $deployment->location->name;
            $tabStatus = $deployment->attendanceTabStatus();
            $attendance = $deployment->attendance;

            if ($tabStatus === 'absent') {
                $alerts->prepend([
                    'severity' => 'critical',
                    'message' => "{$name} is ABSENT — {$location} post unattended",
                    'time' => $deployment->shiftStartAt(),
                ]);
            }

            if ($minutesLate = $attendance?->checkInLatenessMinutes($deployment)) {
                $alerts->prepend([
                    'severity' => 'warning',
                    'message' => "{$name} checked in ".Attendance::formatLateness($minutesLate)." late at {$location}",
                    'time' => $attendance->time_in,
                ]);
            }

            if ($minutesLate = $attendance?->checkOutLatenessMinutes($deployment)) {
                $alerts->prepend([
                    'severity' => 'warning',
                    'message' => "{$name} checked out ".Attendance::formatLateness($minutesLate)." after shift end at {$location}",
                    'time' => $attendance->time_out,
                ]);
            }

            if ($tabStatus === 'on_duty' && $attendance?->status === 'present' && ! $attendance->time_out) {
                $alerts->push([
                    'severity' => 'success',
                    'message' => "{$name} checked in at {$location} — on time",
                    'time' => $attendance->time_in,
                ]);
            }

            if ($attendance?->status === 'completed' && $attendance->time_out) {
                $alerts->push([
                    'severity' => 'success',
                    'message' => "{$name} checked out at {$location}",
                    'time' => $attendance->time_out,
                ]);
            }
        }

        return $alerts->sortByDesc('time')->take(6)->values();
    }

    private function buildCheckInTrend(Collection $todayDeployments): Collection
    {
        $todayCheckIns = Attendance::query()
            ->whereHas('deployment', fn ($query) => $query->whereDate('deployment_date', today()))
            ->whereNotNull('time_in')
            ->get();

        $shiftStarts = $todayDeployments
            ->map(fn (Deployment $deployment) => $deployment->shiftStartAt());

        if ($shiftStarts->isEmpty()) {
            $shiftStarts = collect([today()->setTime(6, 0), today()->setTime(8, 0)]);
        }

        $windowStart = $shiftStarts->min()->copy()->subMinutes(15);
        $windowEnd = $shiftStarts->max()->copy()->addHours(2);

        $slots = collect();
        $slot = $windowStart->copy();

        while ($slot <= $windowEnd) {
            $label = $slot->format('g:i A');
            $count = $todayCheckIns->filter(fn (Attendance $attendance) => $attendance->time_in <= $slot)->count();
            $slots[$label] = $count;
            $slot->addMinutes(15);
        }

        return $slots;
    }
}
