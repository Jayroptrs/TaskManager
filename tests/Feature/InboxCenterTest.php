<?php

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskCommentMention;
use App\Models\TaskDueDateReminder;
use App\Models\User;

test('authenticated user can open inbox center and see mentions tab content', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Inbox schaal test']);
    $task->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Schaalbare inbox melding',
    ]);

    TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index'))
        ->assertOk()
        ->assertSee(__('ui.inbox'))
        ->assertSee(__('ui.mentions'))
        ->assertSee(__('ui.mentioned_you', ['name' => $owner->name]));
});

test('user can switch to invites tab in inbox center', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Invite tab task']);

    TaskCollaborationRequest::query()->create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $receiver->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'invites']))
        ->assertOk()
        ->assertSee(__('ui.invites'))
        ->assertSee(__('ui.invite_for_task', ['title' => $task->title]));
});

test('mark all mentions as read marks unread mention items read', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Unread mention',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($receiver)
        ->post(route('inbox.mentions.read-all'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($mention->fresh()->read_at)->not->toBeNull();
});

test('guest cannot access inbox center routes', function () {
    $this->get(route('inbox.index'))->assertRedirect(route('login'));
    $this->post(route('inbox.mentions.read-all'))->assertRedirect(route('login'));
});

test('invalid inbox tab query falls back to mentions tab', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Fallback task']);
    $task->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Fallback mention',
    ]);

    TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'invalid-tab']))
        ->assertOk()
        ->assertSee(__('ui.mentions'))
        ->assertSee(__('ui.mentioned_you', ['name' => $owner->name]));
});

test('mentions unread filter hides read items while all filter includes them', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Mention filter task']);
    $task->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $unreadComment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Unread mention body',
    ]);
    $readComment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Read mention body',
    ]);

    TaskCommentMention::query()->create([
        'task_comment_id' => $unreadComment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);
    TaskCommentMention::query()->create([
        'task_comment_id' => $readComment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => now()->subMinute(),
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'mentions', 'mention' => 'unread']))
        ->assertOk()
        ->assertSee('Unread mention body')
        ->assertDontSee('Read mention body');

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'mentions', 'mention' => 'all']))
        ->assertOk()
        ->assertSee('Unread mention body')
        ->assertSee('Read mention body');
});

test('invalid mention filter falls back to unread mentions', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $receiver = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Invalid filter task']);
    $task->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $unreadComment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Unread mention for fallback',
    ]);
    $readComment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Read mention for fallback',
    ]);

    TaskCommentMention::query()->create([
        'task_comment_id' => $unreadComment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);
    TaskCommentMention::query()->create([
        'task_comment_id' => $readComment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => now()->subMinute(),
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'mentions', 'mention' => 'bogus']))
        ->assertOk()
        ->assertSee('Unread mention for fallback')
        ->assertDontSee('Read mention for fallback');
});

test('reminders unread filter hides read items while all filter includes them', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();
    $unreadTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Unread reminder task',
        'due_date' => today()->addDays(2),
    ]);
    $readTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Read reminder task',
        'due_date' => today()->addDays(3),
    ]);
    $unreadTask->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);
    $readTask->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    TaskDueDateReminder::query()->create([
        'task_id' => $unreadTask->id,
        'user_id' => $receiver->id,
        'created_by_user_id' => $owner->id,
        'due_date' => today()->addDays(2),
        'days_before' => 1,
        'remind_on_date' => today(),
        'read_at' => null,
    ]);
    TaskDueDateReminder::query()->create([
        'task_id' => $readTask->id,
        'user_id' => $receiver->id,
        'created_by_user_id' => $owner->id,
        'due_date' => today()->addDays(3),
        'days_before' => 1,
        'remind_on_date' => today(),
        'read_at' => now()->subMinute(),
    ]);

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'reminders', 'reminder' => 'unread']))
        ->assertOk()
        ->assertSee('Unread reminder task')
        ->assertDontSee('Read reminder task');

    $this->actingAs($receiver)
        ->get(route('inbox.index', ['tab' => 'reminders', 'reminder' => 'all']))
        ->assertOk()
        ->assertSee('Unread reminder task')
        ->assertSee('Read reminder task');
});
