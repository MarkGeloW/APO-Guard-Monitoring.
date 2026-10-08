<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;

use App\Models\Attendance;

use App\Models\Deployment;

use App\Models\SystemNotification;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class GuardDashboardController extends Controller

{

    public function __invoke(Request $request): JsonResponse

    {

        $user = $request->user()->load('securityPersonnel');

        $personnel = $user->securityPersonnel;



        if (! $personnel) {

            return response()->json(['message' => 'No personnel profile linked to this account.'], 404);

        }



        $schedule = Deployment::scheduleSummaryForPersonnel($personnel->id);

        $focusDeploymentId = $schedule['active_deployment_id'] ?? $schedule['next_deployment_id'];

        $todayDeployment = $focusDeploymentId
            ? Deployment::with(['location', 'attendance'])->find($focusDeploymentId)
            : null;



        $attendance = $todayDeployment?->attendance;

        $location = $todayDeployment?->location;



        $alerts = SystemNotification::query()

            ->where('user_id', $user->id)

            ->latest()

            ->limit(6)

            ->get()

            ->map(fn (SystemNotification $n) => [

                'id' => $n->id,

                'type' => $n->type,

                'tone' => match ($n->severity()) {

                    'error' => 'critical',

                    'warning' => 'warning',

                    'success' => 'success',

                    default => 'info',

                },

                'title' => $n->title,

                'message' => $n->message,

                'time' => $n->created_at?->format('h:i A'),

            ])

            ->values();



        return response()->json([

            'guard' => [

                'name' => $personnel->fullName(),

                'initials' => $personnel->initials(),

                'guard_code' => $personnel->guardCode(),

                'badge_number' => $personnel->badge_number,

            ],

            'status' => $schedule['status'],

            'status_label' => $schedule['status_label'],

            'since' => $schedule['since'],

            'status_message' => $schedule['status_message'],

            'shift' => $todayDeployment?->shiftType() ?? '—',

            'shift_label' => $todayDeployment

                ? today()->format('M j, Y').' · '.$todayDeployment->shiftType().' Shift'

                : today()->format('M j, Y'),

            'deployment' => $todayDeployment ? array_merge(
                $location?->guardDisplayFields() ?? [
                    'post' => null,
                    'client' => null,
                    'area' => null,
                    'city' => null,
                    'address' => null,
                    'location_subtitle' => null,
                ],
                [
                    'id' => $todayDeployment->id,
                    'lat' => $attendance?->time_in_latitude ?? $location?->latitude,
                    'lng' => $attendance?->time_in_longitude ?? $location?->longitude,
                    'shift_start' => $todayDeployment->formattedShiftStart(),
                    'shift_end' => $todayDeployment->formattedShiftEnd(),
                ]
            ) : null,

            'today_schedule' => $schedule['today_schedule'],

            'schedule_count' => count($schedule['today_schedule']),

            'next_deployment_id' => $schedule['next_deployment_id'],

            'alerts' => $alerts,

        ]);

    }

}


