<?php

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskInvite;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authenticated user can open task board index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(__('ui.tasks'));
});

test('user can open calendar view and see tasks grouped by due date', function () {
    $user = User::factory()->create();
    $month = now()->startOfMonth();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Kalender taak deze maand',
        'due_date' => $month->copy()->addDays(5)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Kalender taak buiten maand',
        'due_date' => $month->copy()->addMonthNoOverflow()->startOfMonth()->addDays(10)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Taak zonder deadline',
        'due_date' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['view' => 'calendar', 'month' => $month->format('Y-m')]))
        ->assertOk()
        ->assertSee(__('task.view_calendar'))
        ->assertSee('Kalender taak deze maand')
        ->assertDontSee('Kalender taak buiten maand')
        ->assertDontSee('Taak zonder deadline');
});

test('calendar view uses current month when invalid month query is provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('task.index', ['view' => 'calendar', 'month' => 'invalid-month']));

    $response->assertOk()
        ->assertViewHas('calendarMonth', fn ($month) => $month->format('Y-m') === now()->startOfMonth()->format('Y-m'));
});

test('calendar grid ends on sunday and has full weeks only', function () {
    $user = User::factory()->create();
    $month = '2026-03';

    $response = $this->actingAs($user)
        ->get(route('task.index', ['view' => 'calendar', 'month' => $month]));

    $response->assertOk()
        ->assertViewHas('calendarDays', function ($days) {
            if ($days->isEmpty()) {
                return false;
            }

            return $days->count() % 7 === 0
                && $days->count() <= 42
                && $days->last()->isSunday();
        });
});

test('calendar view renders quick add trigger that opens create modal with selected due date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('task.index', ['view' => 'calendar']))
        ->assertOk()
        ->assertSee('task-create-due-date-selected')
        ->assertSee("\$dispatch('open-modal', 'create-task')", false)
        ->assertSee('@task-create-due-date-selected.window="dueDate = $event.detail?.dueDate ?? dueDate"', false);
});

test('user can create a task and tags are normalized', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('task.store'), [
        'title' => 'Nieuwe taak',
        'description' => 'Omschrijving',
        'status' => 'pending',
        'priority' => 'high',
        'tags' => ['#Nieuw', ' Idee ', 'NIEUW'],
        'links' => ['https://example.com'],
    ]);

    $response->assertRedirect(route('task.index'));

    $task = Task::query()->first();

    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Nieuwe taak');
    expect($task->priority->value)->toBe('high');
    expect($task->tags)->toBe(['nieuw', 'idee']);
    expect($task->image_path)->toBeIn(config('tasks.default_images'));
});

test('task links with www prefix are normalized to https on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('task.store'), [
        'title' => 'Taak met link zonder schema',
        'description' => 'Omschrijving',
        'status' => 'pending',
        'links' => ['www.voorbeeld.com'],
    ])->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->first();

    expect($task)->not->toBeNull();
    expect($task->links)->toBe(['https://www.voorbeeld.com']);
});

test('task links with www prefix are normalized to https on update', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'links' => ['https://old.example.com'],
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Aangepaste taak',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'links' => ['www.example.com/docs'],
            'tags' => [],
        ])
        ->assertStatus(302);

    expect($task->fresh()->links)->toBe(['https://www.example.com/docs']);
});

test('create modal link placeholder follows active locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['locale' => 'nl'])
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('placeholder="www.voorbeeld.com"', false);

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('placeholder="www.example.com"', false);
});

test('task priority defaults to medium when omitted on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('task.store'), [
        'title' => 'Taak zonder prioriteit',
        'description' => 'Omschrijving',
        'status' => 'pending',
    ])->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->first();

    expect($task)->not->toBeNull();
    expect($task->priority->value)->toBe('medium');
});

test('user cannot create more open tasks than configured limit', function () {
    config(['tasks.max_open_tasks' => 1]);
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('task.store'), [
            'title' => 'Nog een open taak',
            'description' => 'Omschrijving',
            'status' => 'in_progress',
        ])
        ->assertSessionHasErrors('status');

    expect(Task::query()->where('user_id', $user->id)->count())->toBe(1);
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

test('filter submit stores last filter in session and apply button uses it', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Team urgent taak',
        'tags' => ['urgent'],
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Andere taak',
        'tags' => ['later'],
    ]);

    $this->actingAs($user)
        ->get(route('task.index', [
            'q' => 'Team',
            'tag' => 'urgent',
            'work' => 'team',
            'sort' => 'oldest',
            'save_last_filter' => 1,
        ]))
        ->assertOk();

    $lastFilter = session('tasks.last_filter');

    expect($lastFilter)->toBeArray();
    expect($lastFilter)->toMatchArray([
        'q' => 'Team',
        'tag' => 'urgent',
        'work' => 'team',
        'sort' => 'oldest',
    ]);

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee(e(route('task.index', $lastFilter)), false)
        ->assertSee(__('task.apply_last_filter'));
});

test('apply last filter button is disabled when no previous filter exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee('data-test="apply-last-filter"', false)
        ->assertSee('aria-disabled="true"', false);
});

test('user can delete own task', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $imagePath = 'ideas/delete-me.jpg';
    Storage::disk('public')->put($imagePath, 'image-content');

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'image_path' => $imagePath,
    ]);

    $this->actingAs($user)
        ->delete(route('task.destroy', $task))
        ->assertRedirect(route('task.index'));

    $this->assertDatabaseMissing('ideas', ['id' => $task->id]);
    Storage::disk('public')->assertMissing($imagePath);
});

test('user can archive task and archived page shows it separately from active tasks', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Te archiveren taak',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->patch(route('task.archive', $task))
        ->assertRedirect(route('task.index'));

    expect($task->fresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('task.index'))
        ->assertOk()
        ->assertDontSee('Te archiveren taak');

    $this->actingAs($user)
        ->get(route('task.archived'))
        ->assertOk()
        ->assertSee('Te archiveren taak')
        ->assertSee(__('task.archived_page_title'))
        ->assertSee(__('task.archived_badge'));
});

test('updating task image removes previous image file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $oldPath = 'ideas/old-task-image.jpg';
    Storage::disk('public')->put($oldPath, 'old-image');
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'image_path' => $oldPath,
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Met nieuwe afbeelding',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'image' => UploadedFile::fake()->image('new-task-image.jpg'),
        ])
        ->assertStatus(302);

    $fresh = $task->fresh();
    expect($fresh->image_path)->not->toBeNull();
    expect($fresh->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($fresh->image_path);
});

test('owner can remove uploaded task image via dedicated endpoint', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $imagePath = 'ideas/remove-via-endpoint.jpg';
    Storage::disk('public')->put($imagePath, 'image-content');
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'image_path' => $imagePath,
    ]);

    $this->actingAs($owner)
        ->delete(route('task.image.destroy', $task))
        ->assertRedirect();

    expect($task->fresh()->image_path)->toBeIn(config('tasks.default_images'));
    Storage::disk('public')->assertMissing($imagePath);
});

test('owner can mark uploaded task image for removal via update flow and save changes', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $imagePath = 'ideas/remove-via-update.jpg';
    Storage::disk('public')->put($imagePath, 'image-content');
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'image_path' => $imagePath,
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->patch(route('task.update', $task), [
            'title' => 'Taak met verwijderde afbeelding',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'remove_image' => 1,
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect();

    expect($task->fresh()->image_path)->toBeIn(config('tasks.default_images'));
    Storage::disk('public')->assertMissing($imagePath);
});

test('owner cannot remove default catalog image path via dedicated endpoint', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $defaultImagePath = (string) (config('tasks.default_images')[0] ?? 'images/task-default-1.svg');
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'image_path' => $defaultImagePath,
    ]);

    $this->actingAs($owner)
        ->delete(route('task.image.destroy', $task))
        ->assertRedirect();

    expect($task->fresh()->image_path)->toBe($defaultImagePath);
});

test('edit modal hides remove image button for default task image and shows lock hint', function () {
    $owner = User::factory()->create();
    $defaultImagePath = (string) (config('tasks.default_images')[0] ?? 'images/task-default-1.svg');
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'image_path' => $defaultImagePath,
    ]);

    $this->actingAs($owner)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee(__('task.default_image_locked_hint'))
        ->assertSee('hasUploadedImage: false', false);
});

test('guest cannot remove task image endpoint', function () {
    $task = Task::factory()->create();

    $this->delete(route('task.image.destroy', $task))
        ->assertRedirect(route('login'));
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

test('collaborator can view and update shared task status', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->get(route('task.show', $task))
        ->assertOk();

    $this->actingAs($collaborator)
        ->patchJson(route('task.status.update', $task), ['status' => 'in_progress'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('owner email invite creates pending request and does not grant access immediately', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => 'invitee@example.com'])
        ->assertRedirect();

    $this->assertDatabaseHas('task_collaboration_requests', [
        'task_id' => $task->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->get(route('task.show', $task))
        ->assertForbidden();
});

test('invitee can accept and reject collaboration requests', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $acceptRequest = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.accept', $acceptRequest))
        ->assertRedirect();

    $this->assertDatabaseHas('idea_collaborators', [
        'idea_id' => $task->id,
        'user_id' => $invitee->id,
    ]);

    $rejectRequest = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.reject', $rejectRequest))
        ->assertRedirect();

    $this->assertDatabaseHas('task_collaboration_requests', [
        'id' => $rejectRequest->id,
        'status' => TaskCollaborationRequest::STATUS_REJECTED,
    ]);
});

test('user can accept invite link and become collaborator', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $invite = TaskInvite::issue($task, $owner);

    $this->actingAs($invitee)
        ->get(route('task.invites.accept', $invite->token))
        ->assertRedirect(route('task.show', $task));

    $this->assertDatabaseHas('idea_collaborators', [
        'idea_id' => $task->id,
        'user_id' => $invitee->id,
    ]);
});

test('collaborator cannot delete task owned by another user', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->delete(route('task.destroy', $task))
        ->assertForbidden();
});

test('collaborator cannot edit task content or image of shared task, but can toggle steps', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'image_path' => 'ideas/test-image.jpg',
    ]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);
    $step = $task->steps()->create([
        'description' => 'Niet bewerkbaar',
        'completed' => false,
    ]);

    $this->actingAs($collaborator)
        ->patch(route('task.update', $task), [
            'title' => 'Collaborator edit',
            'description' => 'Omschrijving',
            'status' => 'pending',
        ])
        ->assertForbidden();

    $this->actingAs($collaborator)
        ->patch(route('step.update', $step))
        ->assertRedirect();

    $this->actingAs($collaborator)
        ->delete(route('task.image.destroy', $task))
        ->assertForbidden();
});

test('owner can remove collaborator from task', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('task.collaborators.destroy', [$task, $collaborator]))
        ->assertRedirect();

    $this->assertDatabaseMissing('idea_collaborators', [
        'idea_id' => $task->id,
        'user_id' => $collaborator->id,
    ]);
});

test('collaborator can leave shared task', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.leave', $task))
        ->assertRedirect(route('task.index'));

    $this->assertDatabaseMissing('idea_collaborators', [
        'idea_id' => $task->id,
        'user_id' => $collaborator->id,
    ]);
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

test('user can update task priority', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => 'low',
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Prioriteit aangepast',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'priority' => 'high',
        ])
        ->assertStatus(302);

    expect($task->fresh()->priority->value)->toBe('high');
});

test('updating task stores detailed activity changes for title priority and due date', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Oude titel',
        'priority' => 'low',
        'due_date' => now()->addDays(2)->toDateString(),
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $task), [
            'title' => 'Nieuwe titel',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => now()->addDays(7)->toDateString(),
            'links' => [],
            'tags' => [],
        ])
        ->assertStatus(302);

    $log = TaskActivityLog::query()
        ->where('idea_id', $task->id)
        ->where('action', 'task_updated')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect(data_get($log?->metadata, 'changes.title.from'))->toBe('Oude titel');
    expect(data_get($log?->metadata, 'changes.title.to'))->toBe('Nieuwe titel');
    expect(data_get($log?->metadata, 'changes.priority.from'))->toBe('low');
    expect(data_get($log?->metadata, 'changes.priority.to'))->toBe('high');
    expect(data_get($log?->metadata, 'changes.due_date.from'))->toBe(now()->addDays(2)->toDateString());
    expect(data_get($log?->metadata, 'changes.due_date.to'))->toBe(now()->addDays(7)->toDateString());

    $this->actingAs($user)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee(__('task.activity_field_title'))
        ->assertSee('Oude titel')
        ->assertSee('Nieuwe titel')
        ->assertSee(__('task.activity_field_priority'))
        ->assertSee(__('task.priority_low'))
        ->assertSee(__('task.priority_high'))
        ->assertSee(__('task.activity_field_due_date'));
});

test('archived open tasks do not count toward open task limit', function () {
    config()->set('tasks.max_open_tasks', 1);

    $user = User::factory()->create();
    $archivedTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'in_progress',
        'archived_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('task.store'), [
            'title' => 'Nieuwe actieve taak',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect(route('task.index'));

    expect(Task::query()->where('user_id', $user->id)->notArchived()->count())->toBe(1);
    expect($archivedTask->fresh()->isArchived())->toBeTrue();
});

test('work filter can separate solo and team tasks', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $otherOwner = User::factory()->create();

    $soloTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Solo taak',
    ]);

    $teamOwnedTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Team taak eigenaar',
    ]);
    $teamOwnedTask->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $teamSharedTask = Task::factory()->create([
        'user_id' => $otherOwner->id,
        'title' => 'Team taak gedeeld',
    ]);
    $teamSharedTask->collaborators()->attach($owner->id, ['added_by' => $otherOwner->id]);

    $this->actingAs($owner)
        ->get(route('task.index', ['work' => 'solo']))
        ->assertOk()
        ->assertSee('Solo taak')
        ->assertDontSee('Team taak eigenaar')
        ->assertDontSee('Team taak gedeeld');

    $this->actingAs($owner)
        ->get(route('task.index', ['work' => 'team']))
        ->assertOk()
        ->assertDontSee('Solo taak')
        ->assertSee('Team taak eigenaar')
        ->assertSee('Team taak gedeeld');
});

test('due filter upcoming only shows tasks due in the next 7 days', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Binnenkort deadline',
        'due_date' => now()->addDays(2)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Te ver in de toekomst',
        'due_date' => now()->addDays(12)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Achterstallige deadline',
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['due' => 'upcoming']))
        ->assertOk()
        ->assertSee('Binnenkort deadline')
        ->assertDontSee('Te ver in de toekomst')
        ->assertDontSee('Achterstallige deadline');
});

test('due filter overdue only shows tasks past their deadline', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Over tijd taak',
        'due_date' => now()->subDays(2)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Toekomst taak',
        'due_date' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['due' => 'overdue']))
        ->assertOk()
        ->assertSee('Over tijd taak')
        ->assertDontSee('Toekomst taak');
});

test('due filter none only shows tasks without deadline', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Zonder deadline',
        'due_date' => null,
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Met deadline',
        'due_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['due' => 'none']))
        ->assertOk()
        ->assertSee('Zonder deadline')
        ->assertDontSee('Met deadline');
});

test('sort can order tasks by nearest deadline first', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Deadline later',
        'due_date' => now()->addDays(8)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Deadline first',
        'due_date' => now()->addDays(2)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'No deadline',
        'due_date' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['sort' => 'deadline_soon']))
        ->assertOk()
        ->assertSeeInOrder(['Deadline first', 'Deadline later', 'No deadline']);
});

test('sort can order tasks by latest deadline first', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Deadline early',
        'due_date' => now()->addDays(1)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Deadline latest',
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'No deadline latest sort',
        'due_date' => null,
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['sort' => 'deadline_late']))
        ->assertOk()
        ->assertSeeInOrder(['Deadline latest', 'Deadline early', 'No deadline latest sort']);
});

test('sort can order tasks by highest priority first', function () {
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Priority medium task',
        'priority' => 'medium',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Priority low task',
        'priority' => 'low',
    ]);

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Priority high task',
        'priority' => 'high',
    ]);

    $this->actingAs($user)
        ->get(route('task.index', ['sort' => 'priority_high']))
        ->assertOk()
        ->assertSeeInOrder(['Priority high task', 'Priority medium task', 'Priority low task']);
});

test('activity log is only visible to task owner', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => 'pending',
    ]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee(__('task.activity_log'));

    $this->actingAs($collaborator)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertDontSee(__('task.activity_log'));
});

test('owner can assign checklist ownership to collaborator on update', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => 'in_progress',
    ]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->patch(route('task.update', $task), [
            'title' => 'Ownership taak',
            'description' => 'Omschrijving',
            'status' => 'in_progress',
            'tags' => ['team'],
            'links' => [],
            'steps' => [
                ['description' => 'Stap met eigenaar', 'completed' => false, 'assigned_user_id' => $collaborator->id],
            ],
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('steps', [
        'idea_id' => $task->id,
        'description' => 'Stap met eigenaar',
        'assigned_user_id' => $collaborator->id,
    ]);
});

test('collaborator can post comment while outsider cannot', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $outsider = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.comments.store', $task), ['comment' => 'Ik help mee'])
        ->assertRedirect();

    $this->assertDatabaseHas('task_comments', [
        'idea_id' => $task->id,
        'user_id' => $collaborator->id,
        'body' => 'Ik help mee',
    ]);

    $this->actingAs($outsider)
        ->post(route('task.comments.store', $task), ['comment' => 'Mag dit?'])
        ->assertForbidden();
});

test('owner can delete collaborator comment but collaborator cannot delete owner comment', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $ownerComment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Owner comment',
    ]);

    $collaboratorComment = $task->comments()->create([
        'user_id' => $collaborator->id,
        'body' => 'Collaborator comment',
    ]);

    $this->actingAs($owner)
        ->delete(route('task.comments.destroy', [$task, $collaboratorComment]))
        ->assertRedirect();

    $this->assertDatabaseMissing('task_comments', ['id' => $collaboratorComment->id]);

    $this->actingAs($collaborator)
        ->delete(route('task.comments.destroy', [$task, $ownerComment]))
        ->assertForbidden();
});

test('outsider cannot delete task comments', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $comment = $task->comments()->create([
        'user_id' => $owner->id,
        'body' => 'Private comment',
    ]);

    $this->actingAs($outsider)
        ->delete(route('task.comments.destroy', [$task, $comment]))
        ->assertForbidden();
});

test('open task limit blocks reopening completed task via board status update', function () {
    config(['tasks.max_open_tasks' => 1]);
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'in_progress',
    ]);

    $completedTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->patchJson(route('task.status.update', $completedTask), ['status' => 'pending'])
        ->assertStatus(422);

    expect($completedTask->fresh()->status->value)->toBe('completed');
});

test('open task limit blocks reopening completed task via update form', function () {
    config(['tasks.max_open_tasks' => 1]);
    $user = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $completedTask = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->patch(route('task.update', $completedTask), [
            'title' => 'Reopen poging',
            'description' => 'Omschrijving',
            'status' => 'in_progress',
        ])
        ->assertSessionHasErrors('status');

    expect($completedTask->fresh()->status->value)->toBe('completed');
});

test('admin can mutate task of another user', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => 'pending',
    ]);

    $step = $task->steps()->create([
        'description' => 'Niet wijzigen',
        'completed' => false,
    ]);

    $this->actingAs($admin)
        ->patch(route('step.update', $step))
        ->assertStatus(302);

    $this->actingAs($admin)
        ->patch(route('task.update', $task), [
            'title' => 'Admin update poging',
            'description' => 'Omschrijving',
            'status' => 'in_progress',
            'steps' => [
                ['description' => 'Nieuwe stap', 'completed' => false],
            ],
        ])
        ->assertStatus(302);

    $this->actingAs($admin)
        ->patchJson(route('task.status.update', $task), ['status' => 'completed'])
        ->assertOk();

    expect($task->fresh()->status->value)->toBe('completed');
    expect($task->fresh()->steps()->where('completed', false)->count())->toBe(0);

    $this->actingAs($admin)
        ->delete(route('task.destroy', $task))
        ->assertRedirect(route('task.index'));

    $this->assertDatabaseMissing('ideas', ['id' => $task->id]);
});
