<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_login_and_fetch_dashboard(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['guard', 'status', 'alerts']);

        $this->withToken($token)
            ->getJson('/api/v1/attendance')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/alerts')
            ->assertOk();
    }

    public function test_non_guard_cannot_login_to_mobile_api(): void
    {
        User::factory()->create([
            'email' => 'admin-mobile@aposecurity.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'admin-mobile@aposecurity.com',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_guard_alerts_only_include_their_own_notifications(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();

        \App\Models\SystemNotification::create([
            'user_id' => $admin->id,
            'type' => 'attendance_absent',
            'title' => 'Guard Absent',
            'message' => 'Leonardo Aquino is ABSENT – Provincial Capitol post unattended',
        ]);

        \App\Models\SystemNotification::create([
            'user_id' => $guard->id,
            'type' => 'deployment_assigned',
            'title' => 'New Deployment Assignment',
            'message' => 'You have been assigned to Kidapawan City Hall today.',
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $alerts = $this->withToken($token)
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->json('alerts');

        $this->assertCount(1, $alerts);
        $this->assertSame('You have been assigned to Kidapawan City Hall today.', $alerts[0]['message']);

        $dashboardAlerts = $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->json('alerts');

        $this->assertCount(1, $dashboardAlerts);
        $this->assertSame('You have been assigned to Kidapawan City Hall today.', $dashboardAlerts[0]['message']);
    }

    public function test_guard_cannot_check_out_before_shift_end(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;
        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->update([
            'shift_start' => '06:00',
            'shift_end' => '23:59',
        ]);

        \App\Models\Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $personnel->id,
                'time_in' => now()->setTime(6, 5),
                'status' => 'present',
            ]
        );

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->assertJson([
                'checked_in' => true,
                'can_check_out' => false,
            ])
            ->assertJsonStructure(['checkout_blocked_message', 'checkout_available_at']);

        $this->withToken($token)
            ->postJson('/api/v1/attendance/check-out', [
                'deployment_id' => $deployment->id,
                'latitude' => $deployment->location->latitude,
                'longitude' => $deployment->location->longitude,
            ])
            ->assertStatus(422);
    }

    public function test_guard_can_mark_alerts_as_read(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();

        $notification = \App\Models\SystemNotification::create([
            'user_id' => $guard->id,
            'type' => 'deployment_assigned',
            'title' => 'New Deployment Assignment',
            'message' => 'Unread alert for guard.',
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->withToken($token)
            ->patchJson("/api/v1/alerts/{$notification->id}/read")
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('alerts.0.unread', false);
    }

    public function test_guard_cannot_check_in_too_early(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;
        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->update([
            'shift_start' => now()->addHours(3)->format('H:i'),
            'shift_end' => now()->addHours(11)->format('H:i'),
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->assertJson([
                'can_check_in' => false,
            ])
            ->assertJsonStructure(['check_in_blocked_message', 'check_in_opens_at']);

        $this->withToken($token)
            ->postJson('/api/v1/attendance/check-in', [
                'deployment_id' => $deployment->id,
                'latitude' => $deployment->location->latitude,
                'longitude' => $deployment->location->longitude,
            ])
            ->assertStatus(422);
    }

    public function test_resolve_for_personnel_prefers_second_shift_after_first_is_completed(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;

        $morning = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $morning->update([
            'shift_start' => '06:00',
            'shift_end' => '10:00',
        ]);

        \App\Models\Attendance::updateOrCreate(
            ['deployment_id' => $morning->id],
            [
                'security_personnel_id' => $personnel->id,
                'time_in' => today()->setTime(6, 5),
                'time_out' => today()->setTime(10, 0),
                'status' => 'completed',
            ]
        );

        $evening = \App\Models\Deployment::create([
            'security_personnel_id' => $personnel->id,
            'location_id' => $morning->location_id,
            'assigned_by' => $morning->assigned_by,
            'deployment_date' => today(),
            'shift_start' => '18:00',
            'shift_end' => '22:00',
            'status' => 'scheduled',
        ]);

        $resolved = \App\Models\Deployment::resolveForPersonnel($personnel->id);

        $this->assertNotNull($resolved);
        $this->assertSame($evening->id, $resolved->id);
    }

    public function test_dashboard_clears_active_assignment_after_shift_is_completed(): void
    {
        \Carbon\Carbon::setTestNow(today()->setTime(20, 35));

        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;

        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->update([
            'shift_start' => '20:32',
            'shift_end' => '20:35',
        ]);

        \App\Models\Attendance::updateOrCreate(
            ['deployment_id' => $deployment->id],
            [
                'security_personnel_id' => $personnel->id,
                'time_in' => today()->setTime(20, 32),
                'time_out' => today()->setTime(20, 35),
                'status' => 'completed',
            ]
        );

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('status', 'off_duty')
            ->assertJsonPath('deployment', null)
            ->assertJsonPath('status_message', 'Your assignment for today is complete.');

        \Carbon\Carbon::setTestNow();
    }

    public function test_dashboard_uses_actual_location_details_for_assigned_post(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;

        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->location->update([
            'name' => 'Carmen Checkpoint',
            'client_name' => 'Carmen Municipal Hall',
            'address' => 'Poblacion, Carmen, North Cotabato',
        ]);

        $deployment->update([
            'shift_start' => now()->subHour()->format('H:i'),
            'shift_end' => now()->addHours(6)->format('H:i'),
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('deployment.post', 'Carmen Checkpoint')
            ->assertJsonPath('deployment.area', 'Carmen Municipal Hall')
            ->assertJsonPath('deployment.city', 'Carmen')
            ->assertJsonPath('deployment.location_subtitle', 'Carmen Municipal Hall · Carmen');
    }
}
