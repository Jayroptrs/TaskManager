<?php

use Illuminate\Support\Facades\Auth;

test('Registers a new user', function (): void {

    visit('/register')
        ->type('name', 'John Doe')
        ->type('email', 'john@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->press('Registreer')
        ->assertPathIs('/tasks');

    $this->assertAuthenticated();

    expect(Auth::user())->toMatchArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('Requires a valid email', function (): void {

    visit('/register')
        ->type('name', 'John Doe')
        ->type('email', 'invalid-email')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->click('Registreer')
        ->assertPathIs('/register');
});
