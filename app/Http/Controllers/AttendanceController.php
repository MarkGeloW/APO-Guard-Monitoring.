<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\DutyPresenceService;
use App\Services\GeolocationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : today();

        $filter = $request->query('filter', 'all');
        $shift = $request->query('shift', 'all');
        $isToday = $date->isSameDay(today());

        // Use operationalToday() for the live day so overnight shifts that started
        // yesterday still appear after midnight (same as Dashboard / Live Map).
        $deployments = $isToday
            ? Deployment::operationalToday()
            : Deployment::with(['personnel', 'location', 'attendance'])
                ->whereDate('deployment_date', $date)
                ->orderBy('shift_start')
                ->orderBy('id')
                ->get();

        $deployments = $deployments
            ->filter(fn (Deployment $deployment) => $shift === 'all' || $deployment->shiftType() === ucfirst($shift))
            ->values();

        $presence = app(DutyPresenceService::class);
        $latestPings = $presence->latestPingsFor($deployments);

        $records = $deployments->map(function (Deployment $deployment) use ($presence, $latestPings) {
            $attendance = $deployment->attendance;
            $signal = $presence->signalFor($deployment, $latestPings->get($deployment->id));

            return [
                'id' => $deployment->id,
                'guard_id' => $deployment->personnel->guardCode(),
                'name' => $deployment->personnel->fullName(),
                'post' => $deployment->location->name,
                'time_in' => Attendance::formatDisplayTime($attendance?->time_in),
                'time_out' => Attendance::formatDisplayTime($attendance?->time_out),
                'shift' => $deployment->shiftType(),
                'gps_verified' => (bool) $attendance?->time_in_verified,
                'face_enrolled' => $deployment->personnel->hasFaceEnrolled(),
                'face_enrolled_url' => $deployment->personnel->faceImageUrl(),
                'time_in_face_verified' => (bool) $attendance?->time_in_face_verified,
                'time_in_face_url' => $attendance?->timeInFaceUrl(),
                'time_out_face_verified' => (bool) $attendance?->time_out_face_verified,
                'time_out_face_url' => $attendance?->timeOutFaceUrl(),
                'tab_status' => $deployment->attendanceTabStatus(),
                'status_label' => $deployment->attendanceStatusLabel(),
                'signal_label' => $signal['label'],
                'signal_tone' => $signal['tone'],
                'deployment_date' => $deployment->deployment_date->format('Y-m-d'),
            ];
        });

        $tabCounts = [
            'all' => $records->count(),
            'on_duty' => $records->where('tab_status', 'on_duty')->count(),
            'late' => $records->where('tab_status', 'late')->count(),
            'absent' => $records->where('tab_status', 'absent')->count(),
            'off_duty' => $records->where('tab_status', 'off_duty')->count(),
        ];

        if ($filter !== 'all') {
            $records = $records->filter(fn (array $record) => $record['tab_status'] === $filter)->values();
        }

        $shiftLabel = match ($shift) {
            'night' => 'Night Shift',
            'day' => 'Day Shift',
            default => 'All Shifts',
        };

        // Include yesterday when exporting the live day so overnight deployments are covered.
        $exportStart = $isToday ? $date->copy()->subDay()->format('Y-m-d') : $date->format('Y-m-d');
        $exportEnd = $date->format('Y-m-d');
        $exportQuery = [
            'start_date' => $exportStart,
            'end_date' => $exportEnd,
        ];

        return Inertia::render('Attendance/Index', [
            'records' => $records->values(),
            'date' => $date->format('M j, Y'),
            'dateValue' => $date->format('Y-m-d'),
            'filter' => $filter,
            'shift' => $shift,
            'shiftLabel' => $shiftLabel,
            'isLiveDay' => $isToday,
            'exportPdfUrl' => route('reports.attendance.pdf', $exportQuery),
            'exportCsvUrl' => route('reports.attendance.export', $exportQuery),
            'tabCounts' => $tabCounts,
            'tabs' => [
                'all' => 'All Guards',
                'on_duty' => 'On Duty',
                'late' => 'Late',
                'absent' => 'Absent',
                'off_duty' => 'Off Duty',
            ],
        ]);
    }

    public function checkIn(Request $request, Deployment $deployment): RedirectResponse
    {
        $user = $request->user();
        $personnel = $user->securityPersonnel;

        if (! $personnel || $deployment->security_personnel_id !== $personnel->id) {
            return back()->with('error', 'You are not authorized for this deployment.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $deployment->load('location');
        $geo = app(GeolocationService::class);
        $distance = (int) $geo->distanceInMeters(
            $validated['latitude'],
            $validated['longitude'],
            $deployment->location->latitude,
            $deployment->location->longitude
        );

        $verified = $geo->isWithinGeofence(
            $validated['latitude'],
            $validated['longitude'],
            $deployment->location->latitude,
            $deployment->location->longitude,
            $deployment->location->geofence_radius
        );

        if (! $verified) {
            return back()->with('error', "You are {$distance}m away from the assigned post. You must be within {$deployment->location->geofence_radius}m to check in.");
        }

        $attendance = Attendance::firstOrCreate(
            ['deployment_id' => $deployment->id],
            ['security_personnel_id' => $personnel->id, 'status' => 'pending']
        );

        if ($attendance->time_in) {
            return back()->with('error', 'You have already checked in for this deployment.');
        }

        if (! $deployment->canCheckInNow()) {
            return back()->with('error', $deployment->checkInBlockedMessage() ?? 'Check-in is not available for this shift right now.');
        }

        $isLate = $deployment->isCheckInLate();

        $attendance->update([
            'time_in' => now(),
            'time_in_latitude' => $validated['latitude'],
            'time_in_longitude' => $validated['longitude'],
            'time_in_distance_meters' => $distance,
            'time_in_verified' => true,
            'status' => $isLate ? 'late' : 'present',
        ]);

        $deployment->update(['status' => 'active']);

        $supervisors = User::whereIn('role', [UserRole::Admin, UserRole::Supervisor])->get();
        foreach ($supervisors as $supervisor) {
            SystemNotification::create([
                'user_id' => $supervisor->id,
                'type' => 'attendance_checkin',
                'title' => 'Guard Checked In',
                'message' => "{$personnel->fullName()} checked in at {$deployment->location->name}.",
                'data' => ['attendance_id' => $attendance->id],
            ]);
        }

        return back()->with('success', 'Check-in successful. Your location has been verified.');
    }

    public function checkOut(Request $request, Deployment $deployment): RedirectResponse
    {
        $user = $request->user();
        $personnel = $user->securityPersonnel;

        if (! $personnel || $deployment->security_personnel_id !== $personnel->id) {
            return back()->with('error', 'You are not authorized for this deployment.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $deployment->load('location');
        $attendance = $deployment->attendance;

        if (! $attendance || ! $attendance->time_in) {
            return back()->with('error', 'You must check in before checking out.');
        }

        if ($attendance->time_out) {
            return back()->with('error', 'You have already checked out.');
        }

        if (! $deployment->canCheckOutNow()) {
            return back()->with('error', 'Check-out is only available after your shift ends at '.$deployment->formattedShiftEnd().'.');
        }

        $geo = app(GeolocationService::class);
        $distance = (int) $geo->distanceInMeters(
            $validated['latitude'],
            $validated['longitude'],
            $deployment->location->latitude,
            $deployment->location->longitude
        );

        $verified = $geo->isWithinGeofence(
            $validated['latitude'],
            $validated['longitude'],
            $deployment->location->latitude,
            $deployment->location->longitude,
            $deployment->location->geofence_radius
        );

        $attendance->update([
            'time_out' => now(),
            'time_out_latitude' => $validated['latitude'],
            'time_out_longitude' => $validated['longitude'],
            'time_out_distance_meters' => $distance,
            'time_out_verified' => $verified,
            'status' => 'completed',
        ]);

        $deployment->update(['status' => 'completed']);

        return back()->with('success', 'Check-out recorded successfully.');
    }
}
