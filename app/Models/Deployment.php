<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deployment extends Model
{
    public const CHECK_IN_EARLY_MINUTES = 60;

    public const CHECK_IN_LATE_GRACE_MINUTES = 2;

    public const CHECK_IN_ABSENT_MINUTES = 15;

    protected $fillable = [
        'security_personnel_id',
        'location_id',
        'assigned_by',
        'deployment_date',
        'shift_start',
        'shift_end',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deployment_date' => 'date',
        ];
    }

    public function formattedShiftStart(): string
    {
        return Carbon::parse($this->shift_start)->format('g:i A');
    }

    public function formattedShiftEnd(): string
    {
        return $this->shiftEndAt()->format('g:i A');
    }

    public function deployCode(): string
    {
        return 'D-'.$this->deployment_date->format('Y').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function shiftRange(): string
    {
        return $this->formattedShiftStart().'–'.$this->formattedShiftEnd();
    }

    public function displayStatus(): string
    {
        $attendance = $this->attendance;
        $attendanceStatus = $attendance?->status ?? 'pending';

        if ($attendance?->time_out || $attendanceStatus === 'completed') {
            return 'completed';
        }

        if ($attendance?->time_in) {
            return $attendanceStatus === 'late' ? 'warning' : 'active';
        }

        if ($this->status === 'scheduled' && $attendanceStatus === 'pending') {
            if (now()->gt($this->checkInAbsentAt())) {
                return 'absent';
            }

            return 'pending';
        }

        if ($attendanceStatus === 'pending' || $attendanceStatus === 'absent') {
            return 'absent';
        }

        if ($this->status === 'active') {
            return 'active';
        }

        return 'pending';
    }

    public function displayStatusLabel(): string
    {
        return match ($this->displayStatus()) {
            'active' => 'Active',
            'warning' => 'Warning',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'absent' => 'Absent',
            default => ucfirst($this->displayStatus()),
        };
    }

    public function shiftType(): string
    {
        $hour = (int) substr((string) $this->shift_start, 0, 2);

        return ($hour >= 18 || $hour < 6) ? 'Night' : 'Day';
    }

    public function shiftStartAt(): Carbon
    {
        return Carbon::parse($this->deployment_date->format('Y-m-d').' '.$this->shift_start);
    }

    public function shiftEndAt(): Carbon
    {
        $start = $this->shiftStartAt();
        $end = Carbon::parse($this->deployment_date->format('Y-m-d').' '.$this->shift_end);

        if ($end->lte($start)) {
            return $end->addDay();
        }

        if ($this->shouldTreatPmEndAsAm($start, $end)) {
            return $end->copy()->subHours(12);
        }

        return $end;
    }

    /**
     * Detects likely AM/PM confusion when an early-AM shift start pairs with a PM
     * end time on the same 12-hour clock (e.g. 2:24 AM start, 2:25 PM stored end
     * that was meant to be 2:25 AM for a short test shift).
     */
    private function shouldTreatPmEndAsAm(Carbon $start, Carbon $end): bool
    {
        $startHour = (int) $start->format('H');
        $endHour = (int) $end->format('H');
        $startMinute = (int) $start->format('i');
        $endMinute = (int) $end->format('i');

        if ($startHour >= 12 || $endHour < 12) {
            return false;
        }

        if (($startHour % 12) !== ($endHour % 12)) {
            return false;
        }

        if (abs($endMinute - $startMinute) > 30) {
            return false;
        }

        $pmDurationMinutes = $start->diffInMinutes($end);
        if ($pmDurationMinutes <= 360) {
            return false;
        }

        $amEnd = $end->copy()->subHours(12);
        if ($amEnd->lte($start)) {
            return false;
        }

        return $start->diffInMinutes($amEnd) <= 120;
    }

    public function canCheckOutNow(): bool
    {
        return now()->gte($this->shiftEndAt());
    }

    public function isWithinShiftWindow(?Carbon $moment = null): bool
    {
        $moment ??= now();

        return $moment->betweenIncluded($this->shiftStartAt(), $this->shiftEndAt());
    }

    public function isOnDuty(): bool
    {
        $attendance = $this->attendance;

        return (bool) $attendance?->time_in && ! $attendance?->time_out;
    }

    public function earliestCheckInAt(): Carbon
    {
        return $this->shiftStartAt()->copy()->subMinutes(self::CHECK_IN_EARLY_MINUTES);
    }

    public function checkInGraceEndsAt(): Carbon
    {
        return $this->shiftStartAt()->copy()->addMinutes(self::CHECK_IN_LATE_GRACE_MINUTES);
    }

    public function checkInAbsentAt(): Carbon
    {
        return $this->shiftStartAt()->copy()->addMinutes(self::CHECK_IN_ABSENT_MINUTES);
    }

    public function isCheckInLate(?Carbon $moment = null): bool
    {
        $moment ??= now();

        return $moment->gt($this->checkInGraceEndsAt());
    }

    public function canCheckInNow(?Carbon $moment = null): bool
    {
        $moment ??= now();

        if ($this->status === 'cancelled') {
            return false;
        }

        if ($this->attendance?->time_in) {
            return false;
        }

        return $moment->gte($this->earliestCheckInAt()) && $moment->lt($this->shiftEndAt());
    }

    public function checkInBlockedMessage(?Carbon $moment = null): ?string
    {
        $moment ??= now();

        if ($this->attendance?->time_in) {
            return null;
        }

        if ($moment->lt($this->earliestCheckInAt())) {
            return 'Check-in opens at '.$this->earliestCheckInAt()->format('g:i A').' (1 hour before your '.$this->formattedShiftStart().' shift).';
        }

        if ($moment->gte($this->shiftEndAt())) {
            return 'This shift ended at '.$this->formattedShiftEnd().'.';
        }

        return null;
    }

    public static function candidatesForPersonnel(int $personnelId): \Illuminate\Support\Collection
    {
        return static::with(['location', 'attendance'])
            ->where('security_personnel_id', $personnelId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('deployment_date', '>=', today()->subDay())
            ->whereDate('deployment_date', '<=', today())
            ->orderBy('deployment_date')
            ->orderBy('shift_start')
            ->get();
    }

    public static function resolveForPersonnel(int $personnelId): ?self
    {
        $candidates = static::candidatesForPersonnel($personnelId);

        $onDuty = $candidates->first(fn (self $deployment) => $deployment->isOnDuty());
        if ($onDuty) {
            return $onDuty;
        }

        $inWindow = $candidates->first(fn (self $deployment) => $deployment->isWithinShiftWindow()
            && ! $deployment->attendance?->time_out);
        if ($inWindow) {
            return $inWindow;
        }

        $actionableToday = $candidates
            ->filter(fn (self $deployment) => $deployment->deployment_date->isToday()
                && ! $deployment->attendance?->time_out
                && now()->lt($deployment->shiftEndAt()))
            ->sortBy(fn (self $deployment) => $deployment->shiftStartAt());

        $checkInReady = $actionableToday->first(fn (self $deployment) => $deployment->canCheckInNow());
        if ($checkInReady) {
            return $checkInReady;
        }

        return $actionableToday->first()
            ?? $candidates->first(fn (self $deployment) => $deployment->deployment_date->isToday());
    }

    public static function todayForPersonnel(int $personnelId): \Illuminate\Support\Collection
    {
        return static::with(['location', 'attendance'])
            ->where('security_personnel_id', $personnelId)
            ->assignable()
            ->whereDate('deployment_date', today())
            ->orderBy('shift_start')
            ->get();
    }

    public static function operationalToday(): \Illuminate\Support\Collection
    {
        $today = static::with(['personnel', 'location', 'attendance'])
            ->whereDate('deployment_date', today())
            ->get();

        $overnight = static::with(['personnel', 'location', 'attendance'])
            ->whereDate('deployment_date', today()->subDay())
            ->get()
            ->filter(fn (self $deployment) => $deployment->isWithinShiftWindow());

        return $today->merge($overnight)->unique('id')->values();
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function attendanceTabStatus(): string
    {
        return match ($this->displayStatus()) {
            'active' => 'on_duty',
            'warning' => 'late',
            'completed' => 'off_duty',
            'pending' => 'off_duty',
            'absent' => 'absent',
            default => 'off_duty',
        };
    }

    public function attendanceStatusLabel(): string
    {
        return match ($this->attendanceTabStatus()) {
            'on_duty' => 'On Duty',
            'late' => 'Late',
            'absent' => 'Absent',
            'off_duty' => $this->displayStatus() === 'completed' ? 'Completed' : 'Off Duty',
            default => 'Off Duty',
        };
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(SecurityPersonnel::class, 'security_personnel_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function overlapsWith(self $other): bool
    {
        if ($this->security_personnel_id !== $other->security_personnel_id) {
            return false;
        }

        if (! $this->deployment_date->isSameDay($other->deployment_date)) {
            return false;
        }

        if ($this->id && $other->id && $this->id === $other->id) {
            return false;
        }

        return $this->shiftStartAt()->lt($other->shiftEndAt())
            && $other->shiftStartAt()->lt($this->shiftEndAt());
    }

    public static function findOverlap(
        int $personnelId,
        string $deploymentDate,
        string $shiftStart,
        string $shiftEnd,
        ?int $ignoreDeploymentId = null,
    ): ?self {
        $candidate = new static([
            'security_personnel_id' => $personnelId,
            'deployment_date' => $deploymentDate,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
        ]);

        return static::query()
            ->with('location')
            ->where('security_personnel_id', $personnelId)
            ->whereDate('deployment_date', $deploymentDate)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreDeploymentId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->get()
            ->first(fn (self $existing) => $candidate->overlapsWith($existing));
    }

    public function scheduleStatus(): string
    {
        $attendance = $this->attendance;

        if ($attendance?->time_out) {
            return 'completed';
        }

        if ($attendance?->time_in && ! $attendance->time_out) {
            return $attendance->status === 'late' ? 'late' : 'on_duty';
        }

        if ($this->canCheckInNow()) {
            return 'ready';
        }

        if (now()->gte($this->shiftEndAt())) {
            return 'missed';
        }

        if (now()->lt($this->earliestCheckInAt())) {
            return 'upcoming';
        }

        return 'pending';
    }

    public function scheduleStatusLabel(): string
    {
        return match ($this->scheduleStatus()) {
            'completed' => 'Completed',
            'on_duty' => 'On Duty',
            'late' => 'Late',
            'ready' => 'Ready to Check In',
            'missed' => 'Missed',
            'upcoming' => 'Upcoming',
            default => 'Pending',
        };
    }

    public static function scheduleSummaryForPersonnel(int $personnelId): array
    {
        $today = static::todayForPersonnel($personnelId);

        if ($today->isEmpty()) {
            return [
                'status' => 'off_duty',
                'status_label' => 'Off Duty',
                'since' => null,
                'status_message' => null,
                'active_deployment_id' => null,
                'next_deployment_id' => null,
                'today_schedule' => [],
            ];
        }

        $onDuty = $today->first(fn (self $deployment) => $deployment->isOnDuty());
        if ($onDuty) {
            $attendance = $onDuty->attendance;

            return [
                'status' => $attendance?->status === 'late' ? 'late' : 'on_duty',
                'status_label' => $attendance?->status === 'late' ? 'Late' : 'On Duty',
                'since' => Attendance::formatDisplayTime($attendance?->time_in),
                'status_message' => null,
                'active_deployment_id' => $onDuty->id,
                'next_deployment_id' => null,
                'today_schedule' => static::mapScheduleItems($today, $onDuty->id),
            ];
        }

        $openShifts = $today
            ->filter(fn (self $deployment) => ! $deployment->attendance?->time_out && now()->lt($deployment->shiftEndAt()))
            ->sortBy(fn (self $deployment) => $deployment->shiftStartAt())
            ->values();

        $readyShift = $openShifts->first(fn (self $deployment) => $deployment->canCheckInNow());
        if ($readyShift) {
            return [
                'status' => 'pending',
                'status_label' => 'Pending Check-In',
                'since' => null,
                'status_message' => null,
                'active_deployment_id' => $readyShift->id,
                'next_deployment_id' => $readyShift->id,
                'today_schedule' => static::mapScheduleItems($today, $readyShift->id),
            ];
        }

        $nextShift = $openShifts->first();
        $completedAny = $today->contains(fn (self $deployment) => (bool) $deployment->attendance?->time_out);

        if ($completedAny && $nextShift && now()->lt($nextShift->earliestCheckInAt())) {
            return [
                'status' => 'between_shifts',
                'status_label' => 'Between Shifts',
                'since' => Attendance::formatDisplayTime(
                    $today->first(fn (self $deployment) => $deployment->attendance?->time_out)?->attendance?->time_out
                ),
                'status_message' => 'Next assignment at '.$nextShift->location?->name
                    .' · check-in opens '.$nextShift->earliestCheckInAt()->format('g:i A'),
                'active_deployment_id' => $nextShift->id,
                'next_deployment_id' => $nextShift->id,
                'today_schedule' => static::mapScheduleItems($today, $nextShift->id),
            ];
        }

        if ($nextShift) {
            return [
                'status' => 'pending',
                'status_label' => 'Pending Check-In',
                'since' => null,
                'status_message' => 'Check-in opens '.$nextShift->earliestCheckInAt()->format('g:i A'),
                'active_deployment_id' => $nextShift->id,
                'next_deployment_id' => $nextShift->id,
                'today_schedule' => static::mapScheduleItems($today, $nextShift->id),
            ];
        }

        return [
            'status' => 'off_duty',
            'status_label' => 'Off Duty',
            'since' => Attendance::formatDisplayTime(
                $today->sortByDesc(fn (self $deployment) => $deployment->shiftStartAt())
                    ->first(fn (self $deployment) => $deployment->attendance?->time_out)
                    ?->attendance?->time_out
            ),
            'status_message' => $today->count() > 1
                ? 'All assignments completed for today.'
                : 'Your assignment for today is complete.',
            'active_deployment_id' => null,
            'next_deployment_id' => null,
            'today_schedule' => static::mapScheduleItems($today, null),
        ];
    }

    public static function mapScheduleItems(\Illuminate\Support\Collection $deployments, ?int $activeId): array
    {
        return $deployments->map(function (self $deployment) use ($activeId) {
            $locationFields = $deployment->location?->guardDisplayFields() ?? [
                'post' => $deployment->location?->name,
                'client' => null,
                'area' => null,
                'city' => null,
                'address' => null,
                'location_subtitle' => null,
            ];

            return array_merge($locationFields, [
                'id' => $deployment->id,
                'shift_start' => $deployment->formattedShiftStart(),
                'shift_end' => $deployment->formattedShiftEnd(),
                'shift_range' => $deployment->shiftRange(),
                'shift_type' => $deployment->shiftType(),
                'schedule_status' => $deployment->scheduleStatus(),
                'schedule_label' => $deployment->scheduleStatusLabel(),
                'checked_in' => $deployment->isOnDuty(),
                'completed' => (bool) $deployment->attendance?->time_out,
                'can_check_in' => $deployment->canCheckInNow(),
                'check_in_opens_at' => $deployment->earliestCheckInAt()->format('g:i A'),
                'time_in' => Attendance::formatDisplayTime($deployment->attendance?->time_in),
                'time_out' => Attendance::formatDisplayTime($deployment->attendance?->time_out),
                'is_active' => $activeId === $deployment->id,
            ]);
        })->values()->all();
    }

    public function toAttendancePayload(bool $isActive = false): array
    {
        $attendance = $this->attendance;
        $location = $this->location;
        $checkedIn = $this->isOnDuty();
        $canCheckOut = $checkedIn && $this->canCheckOutNow();
        $canCheckIn = $this->canCheckInNow();

        return [
            'id' => $this->id,
            'post' => $location?->name,
            'shift_start' => $this->formattedShiftStart(),
            'shift_end' => $this->formattedShiftEnd(),
            'shift_range' => $this->shiftRange(),
            'shift_type' => $this->shiftType(),
            'schedule_status' => $this->scheduleStatus(),
            'schedule_label' => $this->scheduleStatusLabel(),
            'time_in' => Attendance::formatDisplayTime($attendance?->time_in),
            'time_out' => Attendance::formatDisplayTime($attendance?->time_out),
            'checked_in' => $checkedIn,
            'completed' => (bool) $attendance?->time_out,
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'check_in_opens_at' => $this->earliestCheckInAt()->format('g:i A'),
            'check_in_blocked_message' => ! $canCheckIn && ! $checkedIn ? $this->checkInBlockedMessage() : null,
            'checkout_available_at' => $this->formattedShiftEnd(),
            'checkout_blocked_message' => $checkedIn && ! $canCheckOut
                ? 'Check-out opens at '.$this->formattedShiftEnd().' when your shift ends.'
                : null,
            'is_active' => $isActive,
        ];
    }
}
