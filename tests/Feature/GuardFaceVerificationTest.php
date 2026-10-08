<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardFaceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function signature(float $offset = 0.0): string
    {
        $values = [0.2, 0.3, 0.7, 0.3, 0.5, 0.5, 0.4, 0.8, 0.35, 0.75, 0.65, 0.75, 0.15, 0.55, 0.85, 0.55, 0.5, 0.5];

        return json_encode(array_map(fn ($value) => $value + $offset, $values));
    }

    public function test_guard_can_enroll_face_and_check_in_with_face_and_gps(): void
    {
        Storage::fake('public');
        \Carbon\Carbon::setTestNow(today()->setTime(10, 0));
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;
        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->update([
            'shift_start' => '06:00',
            'shift_end' => '18:00',
            'status' => 'scheduled',
        ]);

        \App\Models\Attendance::where('deployment_id', $deployment->id)->delete();

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->post('/api/v1/face/enroll', [
                'face_image' => UploadedFile::fake()->image('enroll.jpg', 640, 640),
                'face_signature' => $this->signature(),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('face_enrolled', true);

        $deployment = $deployment->fresh(['attendance', 'location']);
        $this->assertTrue($deployment->canCheckInNow(), $deployment->checkInBlockedMessage() ?? 'blocked');

        $this->withToken($token)
            ->post('/api/v1/attendance/check-in', [
                'deployment_id' => $deployment->id,
                'latitude' => $deployment->location->latitude,
                'longitude' => $deployment->location->longitude,
                'face_image' => UploadedFile::fake()->image('checkin.jpg', 640, 640),
                'face_signature' => $this->signature(0.01),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('face_verified', true)
            ->assertJsonPath('gps_verified', true);

        $this->assertDatabaseHas('attendances', [
            'deployment_id' => $deployment->id,
            'time_in_face_verified' => 1,
        ]);

        \Carbon\Carbon::setTestNow();
    }

    public function test_check_in_rejected_when_face_not_enrolled(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $personnel = $guard->securityPersonnel;
        $deployment = \App\Models\Deployment::where('security_personnel_id', $personnel->id)
            ->whereDate('deployment_date', today())
            ->firstOrFail();

        $deployment->update([
            'shift_start' => now()->subMinutes(5)->format('H:i'),
            'shift_end' => now()->addHours(6)->format('H:i'),
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'test',
        ])->json('token');

        $this->withToken($token)
            ->post('/api/v1/attendance/check-in', [
                'deployment_id' => $deployment->id,
                'latitude' => $deployment->location->latitude,
                'longitude' => $deployment->location->longitude,
                'face_image' => UploadedFile::fake()->image('checkin.jpg', 640, 640),
                'face_signature' => $this->signature(),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('face_enrolled', false);
    }
}
