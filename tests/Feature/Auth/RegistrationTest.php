<?php

test('registration is disabled for public access', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('public registration is not allowed', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
});
