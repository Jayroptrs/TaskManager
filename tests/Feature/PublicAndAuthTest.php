<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

test('guest can open public legal pages', function () {
    $this->get(route('support'))->assertOk();
    $this->get(route('privacy'))->assertOk();
    $this->get(route('terms'))->assertOk();
});

test('public root aliases redirect to tasks index', function () {
    $this->get('/')->assertRedirect('/tasks');
    $this->get('/ideas')->assertRedirect('/tasks');
});

test('guest cannot access authenticated pages', function () {
    $this->get(route('task.index'))->assertRedirect(route('login'));
    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('non admin cannot access admin user tasks page', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.tasks', $target))
        ->assertForbidden();
});

test('locale update accepts supported locale and rejects invalid locale', function () {
    $this->from(route('support'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('support'))
        ->assertSessionHas('locale', 'en');

    $this->from(route('support'))
        ->post(route('locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('support'))
        ->assertSessionHasErrors('locale');
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'validpassword',
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('authenticated user cannot open login and register pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect('/dashboard');

    $this->actingAs($user)
        ->get('/register')
        ->assertRedirect('/dashboard');
});

test('registration requires password confirmation', function () {
    $this->from('/register')
        ->post('/register', [
            'name' => 'Nieuwe Gebruiker',
            'email' => 'nieuwe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors('password');
});

test('logout endpoint logs user out and rotates session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

test('support submission validates minimum lengths for guests', function () {
    $this->post(route('support.store'), [
        'guest_name' => 'A',
        'guest_email' => 'gast@example.com',
        'subject' => 'Kort',
        'category' => 'account',
        'message' => 'te kort',
    ])->assertSessionHasErrors(['guest_name', 'subject', 'message']);
});

test('support submission fails when recaptcha verification fails', function () {
    config([
        'services.recaptcha.enabled' => true,
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
    ]);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
    ]);

    $this->post(route('support.store'), [
        'guest_name' => 'Gast Gebruiker',
        'guest_email' => 'gast@example.com',
        'subject' => 'Inlog probleem',
        'category' => 'account',
        'message' => 'Ik kan niet inloggen op mijn account en heb nu hulp nodig.',
        'g-recaptcha-response' => 'fake-token',
    ])->assertSessionHasErrors('g-recaptcha-response');
});

test('profile update rejects duplicate email', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create(['email' => 'bestaat@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $otherUser->email,
        ])
        ->assertSessionHasErrors('email');
});

test('profile update without password change keeps existing password hash', function () {
    $user = User::factory()->create(['password' => 'oldpassword123']);
    $oldHash = $user->password;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nieuwe Naam',
            'email' => $user->email,
        ])
        ->assertRedirect(route('profile.edit'));

    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Nieuwe Naam');
    expect($fresh->password)->toBe($oldHash);
    expect(Hash::check('oldpassword123', $fresh->password))->toBeTrue();
});
