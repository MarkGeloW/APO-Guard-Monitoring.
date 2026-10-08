<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\SystemNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $storedAlerts = SystemNotification::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (SystemNotification $notification) => [
                'id' => $notification->id,
                'source' => 'stored',
                'type' => $notification->type,
                'title' => $notification->title ?: $notification->severityLabel(),
                'severity' => $notification->severity(),
                'message' => $notification->message,
                'time' => $notification->created_at,
                'read' => ! $notification->isUnread(),
            ]);

        $liveAlerts = $this->buildLiveAlerts(Deployment::operationalToday());

        $alerts = $storedAlerts
            ->concat($liveAlerts)
            ->unique(fn (array $alert) => ($alert['source'] ?? 'live').'|'.($alert['id'] ?? $alert['message']))
            ->sortByDesc('time')
            ->values()
            ->map(function (array $alert) {
                $time = Carbon::parse($alert['time']);

                return [
                    'id' => $alert['id'],
                    'source' => $alert['source'] ?? 'live',
                    'type' => $alert['type'] ?? null,
                    'title' => $alert['title'] ?? 'Alert',
                    'severity' => $alert['severity'] ?? 'info',
                    'message' => $alert['message'],
                    'read' => (bool) ($alert['read'] ?? false),
                    'time' => $time->toIso8601String(),
                    'time_label' => $time->format('M j, Y · g:i A'),
                    'time_ago' => $time->diffForHumans(),
                ];
            });

        $unreadCount = SystemNotification::where('user_id', $user->id)->whereNull('read_at')->count()
            + $alerts->where('source', 'live')->where('read', false)->count();

        return Inertia::render('Notifications/Index', [
            'alerts' => $alerts,
            'unreadCount' => $unreadCount,
            'stats' => [
                'critical' => $alerts->where('severity', 'error')->count(),
                'warnings' => $alerts->where('severity', 'warning')->count(),
                'updates' => $alerts->whereIn('severity', ['success', 'info'])->count(),
                'unread' => $alerts->where('read', false)->count(),
            ],
        ]);
    }

    public function markAsRead(SystemNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        SystemNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function buildLiveAlerts(Collection $deployments): Collection
    {
        return $deployments->flatMap(function (Deployment $deployment) {
            $alerts = collect();
            $name = $deployment->personnel->fullName();
            $location = $deployment->location->name;
            $attendance = $deployment->attendance;
            $tabStatus = $deployment->attendanceTabStatus();

            if ($tabStatus === 'absent') {
                $alerts->push([
                    'id' => 'live-absent-'.$deployment->id,
                    'source' => 'live',
                    'type' => 'attendance_absent',
                    'title' => 'Absent Post',
                    'severity' => 'error',
                    'message' => "{$name} is absent — {$location} post is currently unattended.",
                    'time' => $attendance?->updated_at ?? Carbon::parse($deployment->deployment_date->format('Y-m-d').' '.$deployment->shift_start),
                    'read' => false,
                ]);
            }

            if ($minutesLate = $attendance?->checkInLatenessMinutes($deployment)) {
                $alerts->push([
                    'id' => 'live-late-in-'.$deployment->id,
                    'source' => 'live',
                    'type' => 'attendance_late',
                    'title' => 'Late Check-In',
                    'severity' => 'warning',
                    'message' => "{$name} checked in ".Attendance::formatLateness($minutesLate)." late at {$location}.",
                    'time' => $attendance->time_in,
                    'read' => false,
                ]);
            }

            if ($minutesLate = $attendance?->checkOutLatenessMinutes($deployment)) {
                $alerts->push([
                    'id' => 'live-late-out-'.$deployment->id,
                    'source' => 'live',
                    'type' => 'attendance_late',
                    'title' => 'Late Check-Out',
                    'severity' => 'warning',
                    'message' => "{$name} checked out ".Attendance::formatLateness($minutesLate)." after shift end at {$location}.",
                    'time' => $attendance->time_out,
                    'read' => false,
                ]);
            }

            if ($tabStatus === 'on_duty' && $attendance?->status === 'present' && ! $attendance->time_out) {
                $alerts->push([
                    'id' => 'live-on-duty-'.$deployment->id,
                    'source' => 'live',
                    'type' => 'attendance_checkin',
                    'title' => 'On Duty',
                    'severity' => 'success',
                    'message' => "{$name} checked in on time at {$location}.",
                    'time' => $attendance->time_in,
                    'read' => false,
                ]);
            }

            if ($attendance?->status === 'completed' && $attendance->time_out) {
                $alerts->push([
                    'id' => 'live-completed-'.$deployment->id,
                    'source' => 'live',
                    'type' => 'attendance_checkin',
                    'title' => 'Shift Completed',
                    'severity' => 'success',
                    'message' => "{$name} checked out at {$location}.",
                    'time' => $attendance->time_out,
                    'read' => false,
                ]);
            }

            return $alerts;
        });
    }
}
