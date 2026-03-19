<?php

use App\Models\Task;
use App\Models\User;
use App\Models\UserAuditLog;
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

test('admin support status update is recorded in user audit log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Audit status wijziging',
        'category' => 'bug',
        'message' => 'Controle op audit.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.support.status', $ticket), [
            'status' => 'waiting_for_user',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_audit_logs', [
        'target_user_id' => $user->id,
        'actor_user_id' => $admin->id,
        'action' => 'admin_support_status_updated',
    ]);

    $audit = UserAuditLog::query()->where('target_user_id', $user->id)->latest('id')->first();
    expect($audit)->not->toBeNull();
    expect(data_get($audit?->metadata, 'support_message_id'))->toBe($ticket->id);
    expect(data_get($audit?->metadata, 'new_status'))->toBe('waiting_for_user');
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

test('admin support reply is recorded in user audit log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $ticket = $user->supportMessages()->create([
        'subject' => 'Audit supportreactie',
        'category' => 'account',
        'message' => 'Controle op audit reply.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.support.reply', $ticket), [
            'message' => 'Kun je extra details delen?',
            'status' => 'waiting_for_user',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_audit_logs', [
        'target_user_id' => $user->id,
        'actor_user_id' => $admin->id,
        'action' => 'admin_support_reply_sent',
    ]);
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

test('admin user tasks page includes tasks where user is collaborator', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $targetUser = User::factory()->create(['name' => 'Samenwerker']);
    $owner = User::factory()->create();

    $ownedTask = Task::factory()->create([
        'user_id' => $targetUser->id,
        'title' => 'Eigen taak van gebruiker',
    ]);

    $collabTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Taak waar gebruiker aan meewerkt',
    ]);
    $collabTask->collaborators()->attach($targetUser->id, ['added_by' => $owner->id]);

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $targetUser))
        ->assertOk()
        ->assertSee($ownedTask->title)
        ->assertSee($collabTask->title)
        ->assertSee(__('admin.user_task_role_owner'))
        ->assertSee(__('admin.user_task_role_collaborator'));
});

test('admin user tasks page shows collaborator emails on task cards', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $targetUser = User::factory()->create([
        'name' => 'Samenwerker',
        'email' => 'samenwerker@example.com',
    ]);
    $owner = User::factory()->create();
    $extraCollaborator = User::factory()->create([
        'name' => 'Tweede Helper',
        'email' => 'tweede.helper@example.com',
    ]);

    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Taak met e-mails',
    ]);
    $task->collaborators()->attach([
        $targetUser->id => ['added_by' => $owner->id],
        $extraCollaborator->id => ['added_by' => $owner->id],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $targetUser))
        ->assertOk()
        ->assertSee($task->title)
        ->assertSee('Samenwerker')
        ->assertSee('samenwerker@example.com')
        ->assertSee('Tweede Helper')
        ->assertSee('tweede.helper@example.com');
});

test('admin user audit section shows task activity from selected user', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['name' => 'Audit Target']);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Audit taak titel',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Audit taak titel bijgewerkt',
            'description' => 'Omschrijving',
            'status' => 'in_progress',
            'links' => [],
            'tags' => [],
        ])
        ->assertStatus(302);

    $this->actingAs($user)
        ->delete(route('task.destroy', $task))
        ->assertRedirect(route('task.index'));

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $user))
        ->assertOk()
        ->assertSee(__('admin.user_audit_title'))
        ->assertSee('Audit taak titel bijgewerkt')
        ->assertSee(__('task.activity_task_updated'))
        ->assertSee(__('task.activity_field_title'))
        ->assertSee('Audit taak titel')
        ->assertSee('Audit taak titel bijgewerkt')
        ->assertSee(__('task.activity_task_deleted'));

    $this->assertDatabaseHas('user_audit_logs', [
        'target_user_id' => $user->id,
        'actor_user_id' => $user->id,
        'action' => 'task_updated',
    ]);
    $this->assertDatabaseHas('user_audit_logs', [
        'target_user_id' => $user->id,
        'actor_user_id' => $user->id,
        'action' => 'task_deleted',
    ]);
});

test('opening admin user tasks page no longer records legacy page-view audit action', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $user))
        ->assertOk();

    $this->assertDatabaseMissing('user_audit_logs', [
        'target_user_id' => $user->id,
        'action' => 'admin_user_tasks_viewed',
    ]);
});

test('creating task writes user audit log entry with task metadata', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('task.store'), [
            'title' => 'Nieuw audit item',
            'description' => 'Taak om create-audit te testen.',
            'status' => 'pending',
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect(route('task.index'));

    $audit = UserAuditLog::query()
        ->where('target_user_id', $user->id)
        ->where('actor_user_id', $user->id)
        ->where('action', 'task_created')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect(data_get($audit?->metadata, 'task_id'))->toBeInt();
    expect(data_get($audit?->metadata, 'task_title'))->toBe('Nieuw audit item');
    expect(data_get($audit?->metadata, 'task_owner_id'))->toBe($user->id);
});

test('admin user audit section filters out non-task actions even when actor is selected user', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['name' => 'Filtered User']);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Zichtbare taakactie',
    ]);
    $task->recordActivity('task_updated', $user->id);

    UserAuditLog::query()->create([
        'target_user_id' => $user->id,
        'actor_user_id' => $user->id,
        'action' => 'admin_support_reply_sent',
        'metadata' => ['note' => 'Should not render in task activity section'],
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $user))
        ->assertOk()
        ->assertSee('Zichtbare taakactie')
        ->assertSee(__('task.activity_task_updated'))
        ->assertDontSee('admin_support_reply_sent')
        ->assertDontSee('Should not render in task activity section');
});

test('admin user audit section only shows activity for selected user', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $selectedUser = User::factory()->create(['name' => 'Selected User']);
    $otherUser = User::factory()->create(['name' => 'Other User']);

    $selectedTask = Task::factory()->create([
        'user_id' => $selectedUser->id,
        'title' => 'Selected user task title',
    ]);
    $otherTask = Task::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Other user task title',
    ]);

    $selectedTask->recordActivity('task_updated', $selectedUser->id);
    $otherTask->recordActivity('task_updated', $otherUser->id);

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $selectedUser))
        ->assertOk()
        ->assertSee('Selected user task title')
        ->assertDontSee('Other user task title');
});

test('admin user audit section is limited to 25 latest task activity rows', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['name' => 'Limited Audit User']);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Limited audit task',
    ]);

    foreach (range(1, 30) as $index) {
        $task->recordActivity('task_updated', $user->id, ['index' => $index]);
    }

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $user))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->count() === 25);
});

test('collaborator task actions are visible in selected user audit with owner metadata', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['name' => 'Collaborator User']);
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Gedeelde audit taak',
        'status' => 'pending',
    ]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->patch(route('task.status.update', $task), [
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.users.tasks', $collaborator))
        ->assertOk()
        ->assertSee('Gedeelde audit taak')
        ->assertSee(__('task.activity_status_changed', [
            'from' => \App\TaskStatus::PENDING->label(),
            'to' => \App\TaskStatus::IN_PROGRESS->label(),
        ]));

    $this->assertDatabaseHas('user_audit_logs', [
        'target_user_id' => $collaborator->id,
        'actor_user_id' => $collaborator->id,
        'action' => 'status_changed',
    ]);

    $audit = UserAuditLog::query()
        ->where('target_user_id', $collaborator->id)
        ->where('action', 'status_changed')
        ->latest('id')
        ->first();

    expect(data_get($audit?->metadata, 'task_owner_id'))->toBe($owner->id);
    expect(data_get($audit?->metadata, 'task_title'))->toBe('Gedeelde audit taak');
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
