<?php

use App\Enums\UserRole;
use App\Models\SecurityPersonnel;
use App\Models\User;

test('admin can access user management', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();
});

test('supervisor cannot access user management', function () {
    $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

    $this->actingAs($supervisor)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('guard cannot sign in to web portal', function () {
    $guard = User::factory()->create(['role' => UserRole::Guard]);
    SecurityPersonnel::create([
        'user_id' => $guard->id,
        'badge_number' => 'SG-TEST',
        'first_name' => 'Test',
        'last_name' => 'Guard',
        'status' => 'active',
    ]);

    $this->post(route('login'), [
        'email' => $guard->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('personnel profile route resolves correctly', function () {
    $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
    $personnel = SecurityPersonnel::create([
        'badge_number' => 'SG-999',
        'first_name' => 'Route',
        'last_name' => 'Test',
        'status' => 'active',
    ]);

    $this->actingAs($supervisor)
        ->get(route('personnel.show', $personnel))
        ->assertOk();
});
