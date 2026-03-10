<?php

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskCommentMention;
use App\Models\SupportMessage;
use App\Models\User;

test('inbox dropdown renders unread mention with link to open mention item', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $mentionedUser = User::factory()->create(['name' => 'Mentioned User']);
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Inbox test task']);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Kun je hiernaar kijken?',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($mentionedUser)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(__('ui.inbox'))
        ->assertSee(__('ui.mentions'))
        ->assertSee(__('ui.mentioned_you', ['name' => $owner->name]))
        ->assertSee(route('inbox.mentions.open', $mention), false);
});

test('read mentions are not shown in inbox mentions list', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id, 'title' => 'Read mention task']);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Dit is al gelezen.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => now(),
    ]);

    $this->actingAs($mentionedUser)
        ->get(route('task.index'))
        ->assertOk()
        ->assertDontSee(route('inbox.mentions.open', $mention), false);
});

test('inbox shows empty state when user has no invites and no unread mentions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(__('ui.no_messages'));
});

test('inbox badge count reflects sum of pending invites and unread mentions', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();
    $inviter = User::factory()->create();
    $taskForMention = Task::factory()->create(['user_id' => $owner->id]);
    $taskForMention->collaborators()->attach($receiver->id, ['added_by' => $owner->id]);

    $comment = $taskForMention->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Ping',
    ]);

    TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $taskForMention->id,
        'mentioned_user_id' => $receiver->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $inviteTask = Task::factory()->create(['user_id' => $inviter->id]);
    TaskCollaborationRequest::query()->create([
        'task_id' => $inviteTask->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $receiver->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $response = $this->actingAs($receiver)->get(route('task.index'));
    $response->assertOk();

    // 1 pending invite + 1 unread mention = 2 items in the inbox badge.
    $response->assertSee('>2<', false);
});

test('inbox mention item is not visible to other authenticated users', function () {
    $owner = User::factory()->create(['name' => 'Owner User']);
    $mentionedUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Alleen voor de genoemde gebruiker.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($otherUser)
        ->get(route('task.index'))
        ->assertOk()
        ->assertDontSee(route('inbox.mentions.open', $mention), false)
        ->assertDontSee(__('ui.mentioned_you', ['name' => $owner->name]));
});

test('inbox dropdown renders unread support update with link', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create(['name' => 'Support Admin']);

    $ticket = $user->supportMessages()->create([
        'subject' => 'Support inbox item',
        'category' => 'bug',
        'message' => 'Bug in de app.',
        'status' => SupportMessage::STATUS_WAITING_FOR_USER,
    ]);

    $reply = $ticket->replies()->create([
        'user_id' => $admin->id,
        'is_admin' => true,
        'message' => 'Kun je extra details delen?',
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(__('ui.support_updates'))
        ->assertSee(__('ui.support_reply_from', ['name' => $admin->name]))
        ->assertSee(route('inbox.support.open', $reply), false);
});
