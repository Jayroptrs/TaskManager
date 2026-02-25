<?php

use App\Models\Task;
use App\Models\User;

test('authenticated user can open task board index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('Mijn taken');
});

test('user can create a task and tags are normalized', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('task.store'), [
        'title' => 'Nieuwe taak',
        'description' => 'Omschrijving',
        'status' => 'pending',
        'tags' => ['#Nieuw', ' Idee ', 'NIEUW'],
        'links' => ['https://example.com'],
    ]);

    $response->assertRedirect(route('task.index'));

    $task = Task::query()->first();

    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Nieuwe taak');
    expect($task->tags)->toBe(['nieuw', 'idee']);
});

test('tag filter is case insensitive and works with hash prefix', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Taak A',
        'tags' => ['Nieuw', 'Idee', '1'],
        'status' => 'pending',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Taak B',
        'tags' => ['nieuw', 'idee', '2'],
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['tag' => 'nieuw']))
        ->assertOk()
        ->assertSee('Taak A')
        ->assertSee('Taak B');

    $this->actingAs($user)
        ->get(route('task.index', ['tag' => '#idee']))
        ->assertOk()
        ->assertSee('Taak A')
        ->assertSee('Taak B');
});

test('user can delete own task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('task.destroy', $task))
        ->assertRedirect(route('task.index'));

    $this->assertDatabaseMissing('ideas', ['id' => $task->id]);
});

test('user cannot view task from another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->get(route('task.show', $task))
        ->assertForbidden();
});

test('user cannot update status of task from another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => 'pending',
    ]);

    $this->actingAs($otherUser)
        ->patchJson(route('task.status.update', $task), ['status' => 'completed'])
        ->assertForbidden();
});

test('user can update task status through board endpoint', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patchJson(route('task.status.update', $task), ['status' => 'completed'])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($task->fresh()->status->value)->toBe('completed');
});

test('board status update to completed marks all task steps as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $task->steps()->createMany([
        ['description' => 'Stap 1', 'completed' => false],
        ['description' => 'Stap 2', 'completed' => false],
    ]);

    $this->actingAs($user)
        ->patchJson(route('task.status.update', $task), ['status' => 'completed'])
        ->assertOk();

    expect($task->fresh()->steps()->where('completed', false)->count())->toBe(0);
});

test('updating task to completed marks provided steps as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Aangepaste taak',
            'description' => 'Omschrijving',
            'status' => 'completed',
            'tags' => ['nieuw'],
            'links' => ['https://example.com'],
            'steps' => [
                ['description' => 'Stap A', 'completed' => false],
                ['description' => 'Stap B', 'completed' => false],
            ],
        ])
        ->assertStatus(302);

    $freshTask = $task->fresh();
    expect($freshTask->status->value)->toBe('completed');
    expect($freshTask->steps()->where('completed', false)->count())->toBe(0);
});
