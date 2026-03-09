<?php

use App\Models\Task;
use App\Models\TaskCommentMention;
use App\Models\User;

test('comment mention by email creates inbox notification for mentioned collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Kan jij dit oppakken @collab@example.com ?',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $collaborator->id,
        'mentioned_by_user_id' => $owner->id,
    ]);
});

test('comment mention by autocomplete style name token creates inbox notification', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Kun jij hiernaar kijken @jane.doe ?',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $collaborator->id,
        'mentioned_by_user_id' => $owner->id,
    ]);
});

test('mention only creates notifications for task participants', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create(['email' => 'outsider@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ping @outsider@example.com',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('task_comment_mentions', 0);
});

test('mentioned user can open inbox item and is redirected to task comment', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Bekijk deze update.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($mentionedUser)
        ->get(route('inbox.mentions.open', $mention))
        ->assertRedirect(route('task.show', $task).'#comment-'.$comment->id);

    expect($mention->fresh()->read_at)->not->toBeNull();
});

test('user cannot open inbox mention that belongs to someone else', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Niet voor jou.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => null,
    ]);

    $this->actingAs($otherUser)
        ->get(route('inbox.mentions.open', $mention))
        ->assertForbidden();
});

test('mentioning collaborator by name does not notify outsider with the same name', function () {
    $owner = User::factory()->create();
    $inTaskJohn = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john-in-task@example.com',
    ]);
    $outsideJohn = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john-outside@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($inTaskJohn->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Kun jij kijken @[John Smith]?',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $inTaskJohn->id,
    ]);
    $this->assertDatabaseMissing('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $outsideJohn->id,
    ]);
});

test('mentioning collaborator by autocomplete name token does not notify outsider with same name', function () {
    $owner = User::factory()->create();
    $inTaskJane = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane-in-task@example.com',
    ]);
    $outsideJane = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane-outside@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($inTaskJane->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ping @jane.doe voor deze taak.',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $inTaskJane->id,
    ]);
    $this->assertDatabaseMissing('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $outsideJane->id,
    ]);
});

test('mentioning collaborator by email does not notify outsider with the same name', function () {
    $owner = User::factory()->create();
    $inTaskJohn = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john-in-task@example.com',
    ]);
    $outsideJohn = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john-outside@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($inTaskJohn->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ping @john-in-task@example.com',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $inTaskJohn->id,
    ]);
    $this->assertDatabaseMissing('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $outsideJohn->id,
    ]);
});

test('multiple autocomplete name token mentions create one notification per mentioned participant', function () {
    $owner = User::factory()->create();
    $first = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    $second = User::factory()->create([
        'name' => 'John Roe',
        'email' => 'john@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($first->id, ['added_by' => $owner->id]);
    $task->collaborators()->attach($second->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Willen @jane.doe en @john.roe dit reviewen?',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $first->id,
    ]);
    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $second->id,
    ]);
});

test('duplicate mentions of same user only create one inbox notification row', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ping @collab@example.com en nog eens @collab@example.com en @['.$collaborator->name.']',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    expect(TaskCommentMention::query()
        ->where('task_comment_id', $comment->id)
        ->where('mentioned_user_id', $collaborator->id)
        ->count())->toBe(1);
});

test('mentioning yourself does not create inbox notification', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ik tag mezelf @owner@example.com @['.$owner->name.']',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('task_comment_mentions', 0);
});

test('collaborator can mention task owner and owner receives notification', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Kun je checken @owner@example.com ?',
        ])
        ->assertRedirect();

    $comment = $task->comments()->latest('id')->first();

    $this->assertDatabaseHas('task_comment_mentions', [
        'task_comment_id' => $comment->id,
        'mentioned_user_id' => $owner->id,
        'mentioned_by_user_id' => $collaborator->id,
    ]);
});

test('guest cannot open mention link and is redirected to login', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Check dit.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
    ]);

    $this->get(route('inbox.mentions.open', $mention))
        ->assertRedirect(route('login'));
});

test('opening already read mention keeps it readable and redirects correctly', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Check dit.',
    ]);

    $mention = TaskCommentMention::query()->create([
        'task_comment_id' => $comment->id,
        'task_id' => $task->id,
        'mentioned_user_id' => $mentionedUser->id,
        'mentioned_by_user_id' => $owner->id,
        'read_at' => now()->subMinute(),
    ]);
    $firstReadAt = $mention->read_at;

    $this->actingAs($mentionedUser)
        ->get(route('inbox.mentions.open', $mention))
        ->assertRedirect(route('task.show', $task).'#comment-'.$comment->id);

    expect($mention->fresh()->read_at?->equalTo($firstReadAt))->toBeTrue();
});

test('mentioned user loses access to mention if removed from task before opening', function () {
    $owner = User::factory()->create();
    $mentionedUser = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($mentionedUser->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Ping @collab@example.com',
        ])
        ->assertRedirect();

    $mention = TaskCommentMention::query()->latest('id')->first();

    $task->collaborators()->detach($mentionedUser->id);

    $this->actingAs($mentionedUser)
        ->get(route('inbox.mentions.open', $mention))
        ->assertForbidden();

    expect($mention->fresh()->read_at)->toBeNull();
});
