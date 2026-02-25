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
        ->assertSee('Totaal taken')
        ->assertSee('2')
        ->assertSee('Top tags')
        ->assertSee('#frontend');
});
