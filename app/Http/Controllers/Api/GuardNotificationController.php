<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $alerts = SystemNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'alerts' => $alerts->map(fn (SystemNotification $n) => [
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
                'unread' => $n->isUnread(),
            ])->values(),
            'unread_count' => SystemNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    public function markAsRead(Request $request, SystemNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($notification->isUnread()) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Alert marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        SystemNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All alerts marked as read.']);
    }
}
