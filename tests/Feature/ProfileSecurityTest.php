<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('updating password requires current password', function () {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertSessionHasErrors('current_password');
});

test('updating password with correct current password works', function () {
    $user = User::factory()->create([
        'password' => 'oldpassword123',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('profile.edit'));

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});
