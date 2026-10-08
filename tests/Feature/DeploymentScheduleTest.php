<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_overlapping_assignments_for_same_guard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();
        $personnel = \App\Models\SecurityPersonnel::firstOrFail();
        $location = \App\Models\Location::firstOrFail();

        $this->actingAs($admin)->post(route('deployments.store'), [
            'security_personnel_id' => $personnel->id,
            'location_id' => $location->id,
            'deployment_date' => today()->format('Y-m-d'),
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'status' => 'scheduled',
        ])->assertRedirect();

        $this->actingAs($admin)->from(route('deployments.create'))
            ->post(route('deployments.store'), [
                'security_personnel_id' => $personnel->id,
                'location_id' => $location->id,
                'deployment_date' => today()->format('Y-m-d'),
                'shift_start' => '10:00',
                'shift_end' => '18:00',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('deployments.create'))
            ->assertSessionHasErrors('shift_end');
    }

    public function test_schedule_summary_reports_between_shifts_status(): void
    {
        Carbon::setTestNow(today()->setTime(15, 0));

        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;

        $morning = Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $morning->update([
            'shift_start' => '06:00',
            'shift_end' => '14:00',
        ]);

        \App\Models\Attendance::updateOrCreate(
            ['deployment_id' => $morning->id],
            [
                'security_personnel_id' => $personnel->id,
                'time_in' => today()->setTime(6, 5),
                'time_out' => today()->setTime(14, 0),
                'status' => 'completed',
            ]
        );

        Deployment::create([
            'security_personnel_id' => $personnel->id,
            'location_id' => $morning->location_id,
            'assigned_by' => $morning->assigned_by,
            'deployment_date' => today(),
            'shift_start' => '18:00',
            'shift_end' => '22:00',
            'status' => 'scheduled',
        ]);

        $summary = Deployment::scheduleSummaryForPersonnel($personnel->id);

        $this->assertSame('between_shifts', $summary['status']);
        $this->assertCount(2, $summary['today_schedule']);
        $this->assertStringContainsString('check-in opens', strtolower($summary['status_message'] ?? ''));

        Carbon::setTestNow();
    }

    public function test_attendance_api_accepts_selected_deployment_id(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;

        $base = Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $evening = Deployment::create([
            'security_personnel_id' => $personnel->id,
            'location_id' => $base->location_id,
            'assigned_by' => $base->assigned_by,
            'deployment_date' => today(),
            'shift_start' => '18:00',
            'shift_end' => '22:00',
            'status' => 'scheduled',
        ]);

        \App\Models\Attendance::create([
            'deployment_id' => $evening->id,
            'security_personnel_id' => $personnel->id,
            'status' => 'pending',
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson("/api/v1/attendance?deployment_id={$evening->id}")
            ->assertOk()
            ->assertJsonPath('deployment_id', $evening->id)
            ->assertJsonCount(2, 'today_deployments');
    }
}
