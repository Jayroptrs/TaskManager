<?php

use App\Models\User;

it('Login a user', function (): void {

    $user = User::factory()->create(['password' => 'password123',]);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password123')
        ->click('@login-button')
        ->assertPathIs('/');

    $this->assertAuthenticated();
});

it('Logs out a user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/')->click('Uitloggen');

    $this->assertGuest();
});