<?php

use App\Models\Task;
use App\Models\User;
use App\TaskStatus;

test('guest cannot access dashboard', function () {
    $this->get(route('dashboard.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can see dashboard stats', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::PENDING,
        'tags' => ['frontend', 'ui'],
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::COMPLETED,
        'tags' => ['frontend', 'api'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee(__('dashboard.kpi_total_tasks'))
        ->assertSee('2')
        ->assertSee(__('dashboard.top_tags'))
        ->assertSee('#frontend');
});

test('dashboard includes tasks shared with collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();

    Task::factory()->create([
        'user_id' => $collaborator->id,
        'status' => TaskStatus::PENDING,
        'title' => 'Eigen taak',
        'tags' => ['eigen'],
    ]);

    $sharedTask = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => TaskStatus::IN_PROGRESS,
        'title' => 'Gedeelde taak',
        'tags' => ['gedeeld'],
    ]);
    $sharedTask->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee(__('dashboard.kpi_total_tasks'))
        ->assertSee('2')
        ->assertSee(__('dashboard.ownership_split'))
        ->assertSee(__('dashboard.owned_tasks'))
        ->assertSee(__('dashboard.collaborative_tasks'))
        ->assertSee('#gedeeld');
});

test('dashboard counts owned task with collaborators as collaborative too', function () {
    $user = User::factory()->create();
    $collaborator = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::PENDING,
        'title' => 'Eigen 1',
    ]);
    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::PENDING,
        'title' => 'Eigen 2',
    ]);
    Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::IN_PROGRESS,
        'title' => 'Eigen 3',
    ]);
    $teamOwned = Task::factory()->create([
        'user_id' => $user->id,
        'status' => TaskStatus::IN_PROGRESS,
        'title' => 'Eigen teamtaak',
    ]);
    $teamOwned->collaborators()->attach($collaborator->id, ['added_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSeeInOrder([
            __('dashboard.owned_tasks'),
            '4',
            __('dashboard.collaborative_tasks'),
            '1',
        ]);
});
