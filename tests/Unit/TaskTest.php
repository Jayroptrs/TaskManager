<?php

use App\TaskPriority;
use App\Models\Task;
use App\Models\User;

test('it belongs to a user', function () {
    $task = Task::factory()->create();

    expect($task->user)->toBeInstanceOf(User::class);
});

test('it can have steps', function () {
    $task = Task::factory()->create();

    expect($task->steps)->toBeEmpty();

    $task->steps()->create([
        'description' => 'Do something',
    ]);

    expect($task->fresh()->steps)->toHaveCount(1);
});

test('it casts priority to task priority enum', function () {
    $task = Task::factory()->create([
        'priority' => 'high',
    ]);

    expect($task->priority)->toBe(TaskPriority::HIGH);
});
