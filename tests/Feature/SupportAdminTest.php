<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('guest can submit support form for login issues', function () {
    $this->post(route('support.store'), [
        'guest_name' => 'Gast Gebruiker',
        'guest_email' => 'gast@example.com',
        'subject' => 'Inlog probleem',
        'category' => 'account',
        'message' => 'Ik kan niet inloggen op mijn account, graag hulp.',
    ])->assertRedirect();

    $this->assertDatabaseHas('support_messages', [
        'user_id' => null,
        'guest_name' => 'Gast Gebruiker',
        'guest_email' => 'gast@example.com',
        'subject' => 'Inlog probleem',
        'category' => 'account',
        'status' => 'open',
    ]);
});

test('authenticated user can submit support message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('support.store'), [
            'subject' => 'Probleem met bordweergave',
            'category' => 'bug',
            'message' => 'Wanneer ik een taak sleep in het bord loopt de kaart soms vast in de browser.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_messages', [
        'user_id' => $user->id,
        'subject' => 'Probleem met bordweergave',
        'category' => 'bug',
        'status' => 'open',
    ]);
});

test('guest support requires recaptcha when enabled', function () {
    config([
        'services.recaptcha.enabled' => true,
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
    ]);

    $this->post(route('support.store'), [
        'guest_name' => 'Gast Gebruiker',
        'guest_email' => 'gast@example.com',
        'subject' => 'Inlog probleem',
        'category' => 'account',
        'message' => 'Ik kan niet inloggen op mijn account, graag hulp.',
    ])->assertSessionHasErrors('g-recaptcha-response');
});

test('non admin cannot access admin panel', function () {
    $user = User::factory()->create(['email' => 'normal@example.com', 'is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});

test('admin can access panel and resolve support message', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Privacy vraag',
        'category' => 'privacy',
        'message' => 'Ik wil weten hoe lang mijn gegevens worden bewaard in jullie systeem.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee('Admin panel')
        ->assertSee('Privacy vraag');

    $this->actingAs($admin)
        ->patch(route('admin.support.resolve', $ticket))
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe('resolved');
});

test('admin can delete a non admin user', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'remove-me@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin deleting a user also removes that users task images from storage', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'remove-images@example.com']);
    $imagePath = 'ideas/admin-delete-user-task.jpg';
    Storage::disk('public')->put($imagePath, 'task-image');
    Task::factory()->create([
        'user_id' => $user->id,
        'image_path' => $imagePath,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    Storage::disk('public')->assertMissing($imagePath);
});

test('admin cannot delete own account via admin panel', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin cannot delete another admin account', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $otherAdmin = User::factory()->admin()->create(['email' => 'other-admin@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $otherAdmin))
        ->assertRedirect()
        ->assertSessionHasErrors('admin');

    $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
});

test('admin can view tasks page for a user', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['name' => 'Doel Gebruiker']);
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Taak voor admin']);

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $user))
        ->assertOk()
        ->assertSee('Doel Gebruiker')
        ->assertSee('Taak voor admin');
});

test('admin can open task detail of another user in read mode', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Admin read taak']);

    $this->actingAs($admin)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee('Admin read taak');
});
