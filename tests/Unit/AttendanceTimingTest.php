<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Deployment;
use App\Models\SecurityPersonnel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_lateness_uses_shift_start_not_total_duty_time(): void
    {
        $this->seed();

        $deployment = Deployment::with('attendance')->firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $deployment->security_personnel_id,
                'time_in' => today()->setTime(6, 42),
                'status' => 'late',
            ]
        );

        $this->assertSame(42, $attendance->checkInLatenessMinutes($deployment->fresh()));
    }

    public function test_on_time_check_in_has_no_lateness_minutes(): void
    {
        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $deployment->security_personnel_id,
                'time_in' => today()->setTime(6, 1),
                'status' => 'present',
            ]
        );

        $this->assertNull($attendance->checkInLatenessMinutes($deployment->fresh()));
    }

    public function test_check_in_within_grace_period_is_not_late(): void
    {
        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $deployment->security_personnel_id,
                'time_in' => today()->setTime(6, 2),
                'status' => 'present',
            ]
        );

        $this->assertNull($attendance->checkInLatenessMinutes($deployment->fresh()));
    }

    public function test_check_in_after_grace_period_is_late(): void
    {
        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $deployment->security_personnel_id,
                'time_in' => today()->setTime(6, 3),
                'status' => 'late',
            ]
        );

        $this->assertSame(3, $attendance->checkInLatenessMinutes($deployment->fresh()));
    }

    public function test_late_check_out_uses_shift_end_not_shift_start(): void
    {
        Carbon::setTestNow(today()->setTime(22, 32));

        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $deployment->security_personnel_id,
                'time_in' => today()->setTime(6, 0),
                'time_out' => today()->setTime(22, 32),
                'status' => 'completed',
            ]
        );

        $deployment = $deployment->fresh();

        $this->assertNull($attendance->checkInLatenessMinutes($deployment));
        $this->assertSame(272, $attendance->checkOutLatenessMinutes($deployment));
        $this->assertSame('4 hours 32 minutes', Attendance::formatLateness(272));
        $this->assertSame('16h 32m', $attendance->formattedDutyDuration());

        Carbon::setTestNow();
    }

    public function test_short_morning_shift_with_pm_end_time_allows_checkout_after_am_end(): void
    {
        Carbon::setTestNow(today()->setTime(2, 28));

        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '02:24',
            'shift_end' => '14:25',
        ]);

        $deployment = $deployment->fresh();

        $this->assertSame('2:25 AM', $deployment->formattedShiftEnd());
        $this->assertTrue($deployment->canCheckOutNow());

        Carbon::setTestNow();
    }

    public function test_standard_day_shift_end_time_is_not_shortened(): void
    {
        $this->seed();

        $deployment = Deployment::firstOrFail();
        $deployment->update([
            'deployment_date' => today(),
            'shift_start' => '06:00',
            'shift_end' => '18:00',
        ]);

        $deployment = $deployment->fresh();

        $this->assertSame('6:00 PM', $deployment->formattedShiftEnd());
        $this->assertFalse($deployment->canCheckOutNow());
    }
}
