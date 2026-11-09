<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BreakGlassAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'apo.break_glass.email' => 'emergency@aposecurity.com',
            'apo.break_glass.name' => 'Emergency Administrator',
            'apo.break_glass.secret' => 'test-break-glass-secret',
        ]);
    }

    public function test_break_glass_command_creates_emergency_admin(): void
    {
        $this->artisan('apo:break-glass', [
            '--secret' => 'test-break-glass-secret',
            '--password' => 'TempPass123!',
        ])->assertSuccessful();

        $user = User::where('email', 'emergency@aposecurity.com')->firstOrFail();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('TempPass123!', $user->password));
    }

    public function test_break_glass_rejects_invalid_secret(): void
    {
        $this->artisan('apo:break-glass', [
            '--secret' => 'wrong-secret',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'emergency@aposecurity.com',
        ]);
    }

    public function test_break_glass_restores_archived_emergency_admin(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'emergency@aposecurity.com',
            'name' => 'Emergency Administrator',
        ]);
        $user->archive();

        $this->assertSoftDeleted($user);

        $this->artisan('apo:break-glass', [
            '--secret' => 'test-break-glass-secret',
            '--password' => 'RecoveredPass123!',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertFalse($user->trashed());
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('RecoveredPass123!', $user->password));
    }

    public function test_break_glass_can_restore_primary_admin(): void
    {
        $primary = User::factory()->admin()->create([
            'email' => 'admin@aposecurity.com',
            'is_active' => false,
        ]);
        $primary->delete();

        $this->artisan('apo:break-glass', [
            '--secret' => 'test-break-glass-secret',
            '--password' => 'TempPass123!',
            '--restore-primary' => true,
        ])->assertSuccessful();

        $primary->refresh();

        $this->assertFalse($primary->trashed());
        $this->assertTrue($primary->is_active);
        $this->assertTrue($primary->isAdmin());
    }

    public function test_break_glass_account_cannot_be_archived_or_deactivated(): void
    {
        $operator = User::factory()->admin()->create([
            'email' => 'operator@aposecurity.com',
        ]);

        $emergency = User::factory()->admin()->create([
            'email' => 'emergency@aposecurity.com',
            'name' => 'Emergency Administrator',
        ]);

        $this->actingAs($operator)
            ->delete(route('users.destroy', $emergency))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($emergency);

        $this->actingAs($operator)
            ->patch(route('users.toggle-status', $emergency))
            ->assertSessionHas('error');

        $this->assertTrue($emergency->fresh()->is_active);
    }

    public function test_last_active_admin_cannot_be_demoted_via_update(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'onlyadmin@aposecurity.com',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::Supervisor->value,
                'is_active' => 0,
            ])
            ->assertRedirect(route('users.index'));

        $admin->refresh();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
    }
}
