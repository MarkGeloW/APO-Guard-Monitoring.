<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GhostAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_guard_cannot_keep_using_an_existing_token(): void
    {
        $this->seed();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'ghost-test',
        ]);

        $login->assertOk();
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $guard->forceFill(['is_active' => false])->saveQuietly();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }

    public function test_archived_guard_cannot_keep_using_an_existing_token(): void
    {
        $this->seed();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
            'device_name' => 'ghost-archive',
        ]);

        $login->assertOk();
        $token = $login->json('token');

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $guard->archive();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }

    public function test_archived_account_cannot_sign_in(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();
        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();

        $this->actingAs($admin)->delete(route('users.destroy', $guard));

        $this->assertSoftDeleted('users', ['id' => $guard->id]);

        $this->postJson('/api/v1/login', [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
        ])->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->post(route('login'), [
            'email' => 'guard1@aposecurity.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_archived_account_can_be_restored(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@aposecurity.com')->firstOrFail();
        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();
        $guard->archive();

        $this->actingAs($admin)
            ->patch(route('users.restore', $guard->id))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $guard->id,
            'deleted_at' => null,
            'is_active' => 1,
        ]);
    }

    public function test_deactivating_user_clears_sessions(): void
    {
        $this->seed();

        $guard = User::where('email', 'guard1@aposecurity.com')->firstOrFail();

        DB::table('sessions')->insert([
            'id' => 'ghost-session',
            'user_id' => $guard->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $guard->update(['is_active' => false]);
        $guard->revokeAccess();

        $this->assertDatabaseMissing('sessions', ['id' => 'ghost-session']);
        $this->assertNull($guard->fresh()->remember_token);
    }
}
