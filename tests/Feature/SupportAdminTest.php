<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('guest can submit support form for login issues', function () {
    config([
        'services.recaptcha.enabled' => false,
        'services.recaptcha.site_key' => null,
        'services.recaptcha.secret_key' => null,
    ]);

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

    expect($ticket->fresh()->status)->toBe('waiting_for_user');
    expect($ticket->fresh()->admin_resolved_at)->not->toBeNull();
    expect($ticket->fresh()->user_resolved_at)->toBeNull();
});

test('admin can update support status to waiting for user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Bug met deadline reminder',
        'category' => 'bug',
        'message' => 'Ik krijg herinneringen op vreemde momenten.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.support.status', $ticket), [
            'status' => 'waiting_for_user',
        ])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe('waiting_for_user');
    expect($ticket->fresh()->resolved_at)->toBeNull();
});

test('admin resolving guest support ticket marks it fully resolved immediately', function () {
    $admin = User::factory()->admin()->create();
    $ticket = \App\Models\SupportMessage::query()->create([
        'guest_name' => 'Guest User',
        'guest_email' => 'guest@example.com',
        'subject' => 'Guest ticket',
        'category' => 'account',
        'message' => 'Vraag zonder account.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.support.resolve', $ticket))
        ->assertRedirect();

    $ticket = $ticket->fresh();
    expect($ticket->status)->toBe('resolved');
    expect($ticket->admin_resolved_at)->not->toBeNull();
    expect($ticket->user_resolved_at)->not->toBeNull();
    expect($ticket->resolved_at)->not->toBeNull();
});

test('admin can send support reply and set status', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Kan niet uploaden',
        'category' => 'bug',
        'message' => 'Profielfoto uploaden lukt niet.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.support.reply', $ticket), [
            'message' => 'Kun je delen welke browser en welke foutmelding je ziet?',
            'status' => 'waiting_for_user',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_message_replies', [
        'support_message_id' => $ticket->id,
        'user_id' => $admin->id,
        'is_admin' => true,
    ]);

    expect($ticket->fresh()->status)->toBe('waiting_for_user');
});

test('user can reply to own support ticket', function () {
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Vraag over account',
        'category' => 'account',
        'message' => 'Ik kan mijn instellingen niet vinden.',
        'status' => 'waiting_for_user',
    ]);

    $this->actingAs($user)
        ->post(route('support.reply', $ticket), [
            'message' => 'Ik gebruik mobiel en zie de knop niet staan.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('support_message_replies', [
        'support_message_id' => $ticket->id,
        'user_id' => $user->id,
        'is_admin' => false,
    ]);

    expect($ticket->fresh()->status)->toBe('in_progress');
});

test('user cannot reply to support ticket of another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ticket = $owner->supportMessages()->create([
        'subject' => 'Security vraag',
        'category' => 'security',
        'message' => 'Wat doen jullie met sessies?',
        'status' => 'open',
    ]);

    $this->actingAs($otherUser)
        ->post(route('support.reply', $ticket), [
            'message' => 'Onrechtmatige reactie',
        ])
        ->assertForbidden();
});

test('user can confirm support ticket as resolved after admin resolved it', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Definitief opgelost',
        'category' => 'bug',
        'message' => 'Issue lijkt opgelost.',
        'status' => 'waiting_for_user',
        'admin_resolved_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->post(route('support.resolve', $ticket))
        ->assertRedirect(route('support'));

    $ticket = $ticket->fresh();
    expect($ticket->status)->toBe('resolved');
    expect($ticket->user_resolved_at)->not->toBeNull();
    expect($ticket->resolved_at)->not->toBeNull();
});

test('fully resolved support ticket is excluded from incoming scope and included in resolved scope', function () {
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Scope test',
        'category' => 'account',
        'message' => 'Controle op inkomend versus afgehandeld.',
        'status' => 'resolved',
        'admin_resolved_at' => now()->subMinutes(2),
        'user_resolved_at' => now()->subMinute(),
        'resolved_at' => now()->subMinute(),
    ]);

    expect(\App\Models\SupportMessage::query()->incoming()->whereKey($ticket->id)->exists())->toBeFalse();
    expect(\App\Models\SupportMessage::query()->fullyResolved()->whereKey($ticket->id)->exists())->toBeTrue();
});

test('support page renders conversation modal for own support ticket', function () {
    $user = User::factory()->create(['name' => 'Support User']);
    $admin = User::factory()->admin()->create(['name' => 'Support Admin']);
    $ticket = $user->supportMessages()->create([
        'subject' => 'Gesprek zichtbaar',
        'category' => 'account',
        'message' => 'Ik zie iets vreemds in mijn account.',
        'status' => 'waiting_for_user',
    ]);

    $ticket->replies()->create([
        'user_id' => $admin->id,
        'is_admin' => true,
        'message' => 'Kun je een screenshot delen?',
    ]);

    $this->actingAs($user)
        ->get(route('support'))
        ->assertOk()
        ->assertSee('support-ticket-'.$ticket->id)
        ->assertSee('Gesprek zichtbaar')
        ->assertSee('Kun je een screenshot delen?');
});

test('support page shows user resolve action when admin has marked ticket resolved', function () {
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Bevestigen opgelost',
        'category' => 'bug',
        'message' => 'Wachten op bevestiging.',
        'status' => 'waiting_for_user',
        'admin_resolved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('support', ['ticket' => $ticket->id]))
        ->assertOk()
        ->assertSee(__('support.mark_resolved'));
});

test('support page renders clickable link to open own support ticket conversation', function () {
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Klikbaar ticket',
        'category' => 'account',
        'message' => 'Ik wil dit ticket openen via de lijst.',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('support'))
        ->assertOk()
        ->assertSee(route('support', ['ticket' => $ticket->id]), false);
});

test('fully resolved support ticket is hidden from user recent support list', function () {
    $user = User::factory()->create();
    $resolvedTicket = $user->supportMessages()->create([
        'subject' => 'Afgehandeld ticket',
        'category' => 'account',
        'message' => 'Dit ticket moet uit de lijst verdwijnen.',
        'status' => 'resolved',
        'admin_resolved_at' => now()->subMinutes(2),
        'user_resolved_at' => now()->subMinute(),
        'resolved_at' => now()->subMinute(),
    ]);
    $openTicket = $user->supportMessages()->create([
        'subject' => 'Open ticket',
        'category' => 'bug',
        'message' => 'Dit ticket moet zichtbaar blijven.',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('support'))
        ->assertOk()
        ->assertDontSee($resolvedTicket->subject)
        ->assertSee($openTicket->subject);
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
