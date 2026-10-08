<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ResolvesGuardDeployment;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\FaceMatchingService;
use App\Services\GeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardAttendanceController extends Controller
{
    use ResolvesGuardDeployment;
    use VerifiesGuardFace;

    public function show(Request $request): JsonResponse
    {
        $personnel = $request->user()->securityPersonnel;

        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        $schedule = Deployment::scheduleSummaryForPersonnel($personnel->id);
        $todayDeployments = Deployment::todayForPersonnel($personnel->id);
        $requestedId = $request->integer('deployment_id') ?: null;

        $selectedDeployment = null;
        if ($requestedId) {
            $selectedDeployment = $todayDeployments->firstWhere('id', $requestedId);
        }

        $todayDeployment = $selectedDeployment
            ?? ($schedule['active_deployment_id']
                ? $todayDeployments->firstWhere('id', $schedule['active_deployment_id'])
                : null)
            ?? Deployment::resolveForPersonnel($personnel->id);

        $activeId = $todayDeployment?->id;

        $history = Deployment::with(['location', 'attendance'])
            ->where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', '<', today())
            ->orderByDesc('deployment_date')
            ->limit(10)
            ->get()
            ->map(function (Deployment $deployment) {
                $attendance = $deployment->attendance;
                $timeIn = $attendance?->time_in;
                $timeOut = $attendance?->time_out;
                $duration = $attendance?->formattedDutyDuration() ?? '—';

                return [
                    'id' => $deployment->id,
                    'date' => $deployment->deployment_date->format('M j, Y'),
                    'range' => (Attendance::formatDisplayTime($timeIn) ?? '—').' → '.(Attendance::formatDisplayTime($timeOut) ?? '—'),
                    'duration' => $duration,
                    'status' => $attendance?->status === 'completed' || ($timeIn && $timeOut)
                        ? 'COMPLETED'
                        : ($timeIn && ! $timeOut ? 'ON DUTY' : strtoupper($attendance?->status ?? 'PENDING')),
                ];
            })
            ->values();

        $attendance = $todayDeployment?->attendance;
        $location = $todayDeployment?->location;
        $checkedIn = (bool) $todayDeployment?->isOnDuty();
        $canCheckOut = $checkedIn && $todayDeployment && $todayDeployment->canCheckOutNow();
        $canCheckIn = $todayDeployment && $todayDeployment->canCheckInNow();

        return response()->json([
            'date_label' => $todayDeployment
                ? today()->format('M j, Y').' · '.$todayDeployment->shiftType().' Shift'
                : today()->format('M j, Y'),
            'deployment_id' => $todayDeployment?->id,
            'status' => $schedule['status'],
            'status_message' => $schedule['status_message'],
            'today_deployments' => Deployment::mapScheduleItems($todayDeployments, $activeId),
            'post' => $location?->name,
            'shift_start' => $todayDeployment?->formattedShiftStart(),
            'shift_end' => $todayDeployment?->formattedShiftEnd(),
            'time_in' => Attendance::formatDisplayTime($attendance?->time_in),
            'time_out' => Attendance::formatDisplayTime($attendance?->time_out),
            'gps_verified' => (bool) $attendance?->time_in_verified,
            'face_verified' => (bool) ($attendance?->time_in_face_verified),
            'face_enrolled' => $personnel->hasFaceEnrolled(),
            'face_required' => true,
            'lat' => $attendance?->time_in_latitude ?? $location?->latitude,
            'lng' => $attendance?->time_in_longitude ?? $location?->longitude,
            'checked_in' => $checkedIn,
            'completed' => (bool) $attendance?->time_out,
            'can_check_in' => $canCheckIn,
            'check_in_opens_at' => $todayDeployment?->earliestCheckInAt()->format('g:i A'),
            'check_in_blocked_message' => $todayDeployment && ! $canCheckIn && ! $checkedIn
                ? $todayDeployment->checkInBlockedMessage()
                : null,
            'can_check_out' => $canCheckOut,
            'checkout_available_at' => $todayDeployment?->formattedShiftEnd(),
            'checkout_blocked_message' => $checkedIn && ! $canCheckOut && $todayDeployment
                ? 'Check-out opens at '.$todayDeployment->formattedShiftEnd().' when your shift ends.'
                : null,
            'check_in_late_minutes' => $attendance && $todayDeployment
                ? $attendance->checkInLatenessMinutes($todayDeployment)
                : null,
            'check_out_late_minutes' => $attendance && $todayDeployment
                ? $attendance->checkOutLatenessMinutes($todayDeployment)
                : null,
            'history' => $history,
        ]);
    }

    public function checkIn(Request $request, FaceMatchingService $matcher): JsonResponse
    {
        $personnel = $request->user()->securityPersonnel;

        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        if ($response = $this->requireEnrolledFace($personnel)) {
            return $response;
        }

        $validated = $request->validate([
            'deployment_id' => ['nullable', 'exists:deployments,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'face_image' => ['required', 'image', 'max:5120'],
            'face_signature' => ['required', 'string'],
        ]);

        $faceResult = $this->verifyFaceCapture($request, $personnel, $matcher, 'check-in');
        if ($faceResult instanceof JsonResponse) {
            return $faceResult;
        }

        $deployment = $this->resolveGuardDeployment(
            $personnel->id,
            $validated['deployment_id'] ?? null,
        );

        if ($response = $this->rejectInactiveDeployment($deployment)) {
            $this->deleteFacePath($faceResult['path']);

            return $response;
        }

        if (! $deployment->canCheckInNow()) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json([
                'message' => $deployment->checkInBlockedMessage() ?? 'Check-in is not available for this shift right now.',
            ], 422);
        }

        $deployment->loadMissing('location');

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

        // Mobile emulators often report far-away GPS; allow soft verify but mark distance.
        $softVerify = $verified || app()->environment('local');

        if (! $softVerify) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json([
                'message' => "You are {$distance}m away from the assigned post. You must be within {$deployment->location->geofence_radius}m to check in.",
            ], 422);
        }

        $attendance = Attendance::firstOrCreate(
            ['deployment_id' => $deployment->id],
            ['security_personnel_id' => $personnel->id, 'status' => 'pending']
        );

        if ($attendance->time_in) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json(['message' => 'You have already checked in for this deployment.'], 422);
        }

        $isLate = $deployment->isCheckInLate();

        $attendance->update([
            'time_in' => now(),
            'time_in_latitude' => $validated['latitude'],
            'time_in_longitude' => $validated['longitude'],
            'time_in_distance_meters' => $distance,
            'time_in_verified' => $verified,
            'time_in_face_verified' => true,
            'time_in_face_path' => $faceResult['path'],
            'time_in_face_score' => $faceResult['score'],
            'status' => $isLate ? 'late' : 'present',
        ]);

        $attendance->refresh();
        $checkInLateMinutes = $attendance->checkInLatenessMinutes($deployment);

        $deployment->update(['status' => 'active']);

        $supervisors = User::whereIn('role', [UserRole::Admin, UserRole::Supervisor])->get();
        foreach ($supervisors as $supervisor) {
            SystemNotification::create([
                'user_id' => $supervisor->id,
                'type' => $checkInLateMinutes ? 'attendance_late' : 'attendance_checkin',
                'title' => $checkInLateMinutes ? 'Late Check-In' : 'Guard Checked In',
                'message' => $checkInLateMinutes
                    ? "{$personnel->fullName()} checked in ".Attendance::formatLateness($checkInLateMinutes)." late at {$deployment->location->name} (face verified)."
                    : "{$personnel->fullName()} checked in at {$deployment->location->name} (face + GPS verified).",
                'data' => ['attendance_id' => $attendance->id],
            ]);
        }

        return response()->json([
            'message' => $checkInLateMinutes
                ? 'Check-in recorded. Face verified. You are '.Attendance::formatLateness($checkInLateMinutes).' late.'
                : ($verified
                    ? 'Check-in successful. Face and location verified.'
                    : 'Check-in recorded with face verification (local GPS mode).'),
            'time_in' => Attendance::formatDisplayTime($attendance->time_in),
            'check_in_late_minutes' => $checkInLateMinutes,
            'gps_verified' => $verified,
            'face_verified' => true,
            'face_score' => $faceResult['score'],
        ]);
    }

    public function checkOut(Request $request, FaceMatchingService $matcher): JsonResponse
    {
        $personnel = $request->user()->securityPersonnel;

        if (! $personnel) {
            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);
        }

        if ($response = $this->requireEnrolledFace($personnel)) {
            return $response;
        }

        $validated = $request->validate([
            'deployment_id' => ['nullable', 'exists:deployments,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'face_image' => ['required', 'image', 'max:5120'],
            'face_signature' => ['required', 'string'],
        ]);

        $faceResult = $this->verifyFaceCapture($request, $personnel, $matcher, 'check-out');
        if ($faceResult instanceof JsonResponse) {
            return $faceResult;
        }

        $deployment = $this->resolveGuardDeployment(
            $personnel->id,
            $validated['deployment_id'] ?? null,
        );

        if ($response = $this->rejectInactiveDeployment($deployment)) {
            $this->deleteFacePath($faceResult['path']);

            return $response;
        }

        $deployment->loadMissing(['location', 'attendance']);
        $attendance = $deployment->attendance;

        if (! $attendance || ! $attendance->time_in) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json(['message' => 'You must check in before checking out.'], 422);
        }

        if ($attendance->time_out) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json(['message' => 'You have already checked out.'], 422);
        }

        if (! $deployment->canCheckOutNow()) {
            $this->deleteFacePath($faceResult['path']);

            return response()->json([
                'message' => 'Check-out is only available after your shift ends at '.$deployment->formattedShiftEnd().'.',
            ], 422);
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
            'time_out_verified' => $verified || app()->environment('local'),
            'time_out_face_verified' => true,
            'time_out_face_path' => $faceResult['path'],
            'time_out_face_score' => $faceResult['score'],
            'status' => 'completed',
        ]);

        $deployment->update(['status' => 'completed']);

        $checkOutLateMinutes = $attendance->checkOutLatenessMinutes($deployment);
        if ($checkOutLateMinutes) {
            $lateness = Attendance::formatLateness($checkOutLateMinutes);
            $supervisors = User::whereIn('role', [UserRole::Admin, UserRole::Supervisor])->get();

            foreach ($supervisors as $supervisor) {
                SystemNotification::create([
                    'user_id' => $supervisor->id,
                    'type' => 'attendance_late',
                    'title' => 'Late Check-Out',
                    'message' => "{$personnel->fullName()} checked out {$lateness} after shift end at {$deployment->location->name} (face verified).",
                    'data' => ['attendance_id' => $attendance->id],
                ]);
            }
        }

        return response()->json([
            'message' => $checkOutLateMinutes
                ? 'Check-out recorded with face verification. You finished '.Attendance::formatLateness($checkOutLateMinutes).' after your scheduled shift end.'
                : 'Check-out recorded successfully. Face verified.',
            'time_out' => Attendance::formatDisplayTime($attendance->fresh()->time_out),
            'check_out_late_minutes' => $checkOutLateMinutes,
            'gps_verified' => $verified || app()->environment('local'),
            'face_verified' => true,
            'face_score' => $faceResult['score'],
        ]);
    }
}
