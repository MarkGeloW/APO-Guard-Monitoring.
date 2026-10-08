<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Deployment;
use App\Models\DutyPing;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DutyPresenceService
{
    public const INTERVAL_MINUTES = 30;

    public const STALE_MINUTES = 40;

    public function record(Deployment $deployment, string $clientUuid, float $latitude, float $longitude, Carbon $capturedAt): DutyPing
    {
        $existing = DutyPing::query()->where('client_uuid', $clientUuid)->first();
        if ($existing) {
            return $existing;
        }

        $deployment->loadMissing(['location', 'attendance', 'personnel']);
        $geo = app(GeolocationService::class);
        $distance = (int) round($geo->distanceInMeters(
            $latitude,
            $longitude,
            (float) $deployment->location->latitude,
            (float) $deployment->location->longitude,
        ));
        $within = $distance <= (int) $deployment->location->geofence_radius;

        $ping = DutyPing::create([
            'client_uuid' => $clientUuid,
            'security_personnel_id' => $deployment->security_personnel_id,
            'deployment_id' => $deployment->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_meters' => $distance,
            'within_geofence' => $within,
            'captured_at' => $capturedAt,
        ]);

        if (! $within) {
            $this->notifyOnce(
                'duty_off_post',
                'Guard left the post',
                "{$deployment->personnel->fullName()} checked in at {$deployment->location->name} but the latest duty ping is {$distance}m outside the geofence.",
                $deployment,
            );
        }

        return $ping;
    }

    /**
     * @param  Collection<int, DutyPing>|null  $latestByDeployment
     * @return array{state: string, label: string, tone: string, minutes: int|null, within_geofence: bool|null, captured_at: string|null}
     */
    public function signalFor(Deployment $deployment, ?DutyPing $lastPing = null): array
    {
        $attendance = $deployment->attendance;
        $onDuty = $attendance?->time_in && ! $attendance->time_out;

        if (! $onDuty) {
            return [
                'state' => 'idle',
                'label' => 'Not on duty',
                'tone' => 'slate',
                'minutes' => null,
                'within_geofence' => null,
                'captured_at' => null,
            ];
        }

        $anchor = $lastPing?->captured_at ?? $attendance->time_in;
        $minutes = (int) abs($anchor->diffInMinutes(now()));

        if ($lastPing && ! $lastPing->within_geofence && $minutes <= self::STALE_MINUTES) {
            return $this->payload('off_post', 'Outside post', 'red', $minutes, $lastPing);
        }

        if ($minutes > self::STALE_MINUTES) {
            $deployment->loadMissing('personnel', 'location');
            $this->notifyOnce(
                'duty_signal_lost',
                'Missed duty check',
                "{$deployment->personnel->fullName()} is still checked in at {$deployment->location->name} but has not sent a duty check for {$minutes} minutes.",
                $deployment,
            );

            return $this->payload('missed', 'No signal', 'red', $minutes, $lastPing);
        }

        if (! $lastPing) {
            return $this->payload('waiting', 'Waiting for first check', 'amber', $minutes, null);
        }

        return $this->payload('on_post', 'On post', 'green', $minutes, $lastPing);
    }

    /**
     * @param  Collection<int, Deployment>  $deployments
     * @return Collection<int, DutyPing>
     */
    public function latestPingsFor(Collection $deployments): Collection
    {
        $ids = $deployments->pluck('id')->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return DutyPing::query()
            ->whereIn('deployment_id', $ids)
            ->orderByDesc('captured_at')
            ->get()
            ->unique('deployment_id')
            ->keyBy('deployment_id');
    }

    private function payload(string $state, string $label, string $tone, int $minutes, ?DutyPing $ping): array
    {
        return [
            'state' => $state,
            'label' => $label,
            'tone' => $tone,
            'minutes' => $minutes,
            'within_geofence' => $ping?->within_geofence,
            'captured_at' => $ping?->captured_at?->toIso8601String(),
        ];
    }

    private function notifyOnce(string $type, string $title, string $message, Deployment $deployment): void
    {
        $recent = SystemNotification::query()
            ->where('type', $type)
            ->where('created_at', '>=', now()->subMinutes(self::STALE_MINUTES))
            ->latest()
            ->limit(40)
            ->get()
            ->contains(fn (SystemNotification $notification) => (int) ($notification->data['deployment_id'] ?? 0) === $deployment->id);

        if ($recent) {
            return;
        }

        $recipients = User::query()->whereIn('role', [UserRole::Admin, UserRole::Supervisor])->get();
        foreach ($recipients as $recipient) {
            SystemNotification::create([
                'user_id' => $recipient->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => [
                    'deployment_id' => $deployment->id,
                    'personnel_id' => $deployment->security_personnel_id,
                ],
            ]);
        }
    }
}
