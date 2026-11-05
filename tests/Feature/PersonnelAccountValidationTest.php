<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Deployment;
use App\Models\SecurityPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'badge_number' => 'SG-999',
                'first_name' => 'No',
                'last_name' => 'Account',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_creating_guard_account_also_creates_personnel_profile(): void
    {
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
                'badge_number' => 'SG-100',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'newguard@aposecurity.com')->firstOrFail();

        $this->assertDatabaseHas('security_personnel', [
            'user_id' => $user->id,
            'badge_number' => 'SG-100',
            'first_name' => 'New',
            'last_name' => 'Guard',
        ]);
    }

    public function test_removing_personnel_archives_record_and_cancels_open_deployments(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();
        $personnel = SecurityPersonnel::where('badge_number', 'SG-001')->firstOrFail();

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
