<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Deployment;
use App\Models\SecurityPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonnelAccountValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_cannot_be_created_without_a_user_management_account(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('personnel.store'), [
                'license_number' => 'SG-999',
                'first_name' => 'No',
                'last_name' => 'Account',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_creating_guard_account_also_creates_personnel_profile(): void
    {
        Storage::fake('public');
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Guard',
                'email' => 'newguard@aposecurity.com',
                'role' => UserRole::Guard->value,
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => 1,
                'license_number' => 'LIC-100',
                'id_pictures' => [
                    UploadedFile::fake()->image('id-front.jpg', 800, 600),
                ],
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'newguard@aposecurity.com')->firstOrFail();
        $personnel = SecurityPersonnel::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('LIC-100', $personnel->license_number);
        $this->assertSame('New', $personnel->first_name);
        $this->assertSame('Guard', $personnel->last_name);
        $this->assertTrue($personnel->hasIdPicture());
    }

    public function test_guard_account_requires_id_picture(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'No Id Guard',
                'email' => 'noid@aposecurity.com',
                'role' => UserRole::Guard->value,
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => 1,
                'license_number' => 'LIC-101',
            ])
            ->assertSessionHasErrors('id_pictures');
    }

    public function test_removing_personnel_archives_record_and_cancels_open_deployments(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();
        $personnel = SecurityPersonnel::where('license_number', 'SG-001')->firstOrFail();

        Deployment::where('security_personnel_id', $personnel->id)
            ->whereIn('status', ['scheduled', 'active'])
            ->update(['status' => 'scheduled']);

        $openIds = Deployment::where('security_personnel_id', $personnel->id)
            ->whereIn('status', ['scheduled', 'active'])
            ->pluck('id');

        $this->assertNotEmpty($openIds);

        $this->actingAs($admin)
            ->delete(route('personnel.destroy', $personnel))
            ->assertRedirect(route('personnel.index', ['archived' => 1]));

        $this->assertSoftDeleted('security_personnel', ['id' => $personnel->id]);
        $this->assertSoftDeleted('users', ['id' => $personnel->user_id]);

        foreach ($openIds as $id) {
            $this->assertDatabaseHas('deployments', [
                'id' => $id,
                'status' => 'cancelled',
            ]);
        }
    }
}
