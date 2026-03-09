<?php

use App\Models\Task;
use App\Models\User;

test('task comments show user avatar when set', function () {
    $owner = User::factory()->create(['avatar_path' => 'avatars/owner.jpg']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Comment met avatar.',
    ]);

    $this->actingAs($owner)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee('/storage/avatars/owner.jpg', false);
});

test('task comments show anonymous avatar when user has no profile photo', function () {
    $owner = User::factory()->create(['avatar_path' => null]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Comment zonder avatar.',
    ]);

    $this->actingAs($owner)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee('/images/avatar-anonymous.svg', false);
});
