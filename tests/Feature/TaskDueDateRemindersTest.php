<?php

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskDueDateReminder;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('creating a task with due date creates default reminders for owner at 7, 3 and 1 days before', function () {
    $owner = User::factory()->create();
    $dueDate = now()->addDays(10)->toDateString();

    $this->actingAs($owner)
        ->post(route('task.store'), [
            'title' => 'Taak met deadline',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => $dueDate,
        ])
        ->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->firstOrFail();

    $reminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->orderByDesc('days_before')
        ->get();

    expect($reminders)->toHaveCount(3);
    expect($reminders->pluck('days_before')->all())->toBe([7, 3, 1]);
    expect($reminders->pluck('remind_on_date')->map->toDateString()->all())->toBe([
        now()->addDays(3)->toDateString(),
        now()->addDays(7)->toDateString(),
        now()->addDays(9)->toDateString(),
    ]);
});

test('custom reminder days are stored and used when creating reminders', function () {
    $owner = User::factory()->create();
    $dueDate = now()->addDays(12)->toDateString();

    $this->actingAs($owner)
        ->post(route('task.store'), [
            'title' => 'Taak met custom reminders',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => $dueDate,
            'reminder_days' => [5, 2],
        ])
        ->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->firstOrFail();

    expect($task->reminder_days)->toBe([2, 5]);

    $reminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->orderByDesc('days_before')
        ->get();

    expect($reminders)->toHaveCount(2);
    expect($reminders->pluck('days_before')->all())->toBe([5, 2]);
});

test('creating a task skips reminder days that are already in the past at creation time', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('task.store'), [
            'title' => 'Deadline morgen',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => now()->addDay()->toDateString(),
            'reminder_days' => [7, 3, 1],
        ])
        ->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->firstOrFail();

    $reminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->orderByDesc('days_before')
        ->get();

    expect($reminders)->toHaveCount(1);
    expect($reminders->pluck('days_before')->all())->toBe([1]);
    expect($reminders->pluck('remind_on_date')->map->toDateString()->all())->toBe([
        now()->toDateString(),
    ]);
});

test('creating task with reminders disabled stores no reminder days and creates no reminder rows', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('task.store'), [
            'title' => 'No reminders task',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => now()->addDays(10)->toDateString(),
            'reminders_enabled' => 0,
        ])
        ->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->firstOrFail();

    expect($task->reminder_days)->toBe([]);
    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->count())->toBe(0);
});

test('updating due date and reminder days refreshes reminders and resets unread state', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDays(6)->toDateString(),
        'reminder_days' => [7, 3, 1],
        'status' => 'pending',
    ]);

    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);
    $task->syncDueDateReminders($owner->id);

    TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $collaborator->id)
        ->where('days_before', 1)
        ->update(['read_at' => now()->subMinute()]);

    $this->actingAs($owner)
        ->patch(route('task.update', $task), [
            'title' => 'Aangepaste titel',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => now()->addDays(15)->toDateString(),
            'reminder_days' => [5, 1],
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect();

    $ownerReminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->orderByDesc('days_before')
        ->get();

    $collaboratorReminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $collaborator->id)
        ->orderByDesc('days_before')
        ->get();

    expect($ownerReminders)->toHaveCount(2);
    expect($ownerReminders->pluck('days_before')->all())->toBe([5, 1]);
    expect($ownerReminders->whereNull('read_at'))->toHaveCount(2);

    expect($collaboratorReminders)->toHaveCount(2);
    expect($collaboratorReminders->pluck('days_before')->all())->toBe([5, 1]);
    expect($collaboratorReminders->whereNull('read_at'))->toHaveCount(2);
});

test('updating task with reminders disabled removes all existing reminder rows', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDays(12)->toDateString(),
        'reminder_days' => [7, 3, 1],
        'status' => 'pending',
    ]);

    $task->syncDueDateReminders($owner->id);
    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->count())->toBe(3);

    $this->actingAs($owner)
        ->patch(route('task.update', $task), [
            'title' => 'No reminders anymore',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => $task->due_date->toDateString(),
            'reminders_enabled' => 0,
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect();

    expect($task->fresh()->reminder_days)->toBe([]);
    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->count())->toBe(0);
});

test('accepting collaboration request on task with due date creates reminders for invitee', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDays(5)->toDateString(),
        'reminder_days' => [3, 1],
    ]);

    $task->syncDueDateReminders($owner->id);

    $request = TaskCollaborationRequest::query()->create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.accept', $request))
        ->assertRedirect();

    $inviteeReminders = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $invitee->id)
        ->orderByDesc('days_before')
        ->get();

    expect($inviteeReminders)->toHaveCount(2);
    expect($inviteeReminders->pluck('days_before')->all())->toBe([3, 1]);
});

test('removing collaborator also removes due date reminders for that collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDays(10)->toDateString(),
        'reminder_days' => [7, 3, 1],
    ]);

    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);
    $task->syncDueDateReminders($owner->id);

    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->where('user_id', $collaborator->id)->count())->toBe(3);

    $this->actingAs($owner)
        ->delete(route('task.collaborators.destroy', [$task, $collaborator]))
        ->assertRedirect();

    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->where('user_id', $collaborator->id)->count())->toBe(0);
});

test('reminders are removed when due date is cleared from task', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'status' => 'pending',
        'due_date' => now()->addDays(3)->toDateString(),
        'reminder_days' => [7, 3, 1],
    ]);

    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);
    $task->syncDueDateReminders($owner->id);

    $this->actingAs($owner)
        ->patch(route('task.update', $task), [
            'title' => 'Taak zonder deadline',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => '',
            'reminder_days' => [7, 3, 1],
            'links' => [],
            'tags' => [],
        ])
        ->assertRedirect();

    expect(TaskDueDateReminder::query()->where('task_id', $task->id)->count())->toBe(0);
});

test('due reminder can be opened and is marked as read', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDay()->toDateString(),
        'reminder_days' => [1],
    ]);

    $task->syncDueDateReminders($owner->id);

    $reminder = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->get(route('inbox.reminders.open', $reminder))
        ->assertRedirect(route('task.show', $task).'#task-deadline');

    expect($reminder->fresh()->read_at)->not->toBeNull();
});

test('future reminder cannot be opened before remind date', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDays(20)->toDateString(),
        'reminder_days' => [7],
    ]);

    $task->syncDueDateReminders($owner->id);

    $reminder = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->get(route('inbox.reminders.open', $reminder))
        ->assertNotFound();
});

test('user cannot open due date reminder that belongs to someone else', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_date' => now()->addDay()->toDateString(),
        'reminder_days' => [1],
    ]);

    $task->syncDueDateReminders($owner->id);

    $reminder = TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($otherUser)
        ->get(route('inbox.reminders.open', $reminder))
        ->assertForbidden();
});

test('mark all reminders as read only marks currently due reminders for current user', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $dueTaskForFirstUser = Task::factory()->create([
        'user_id' => $firstUser->id,
        'due_date' => now()->addDay()->toDateString(),
        'reminder_days' => [1],
    ]);
    $dueTaskForFirstUser->syncDueDateReminders($firstUser->id);

    $futureTaskForFirstUser = Task::factory()->create([
        'user_id' => $firstUser->id,
        'due_date' => now()->addDays(20)->toDateString(),
        'reminder_days' => [7],
    ]);
    $futureTaskForFirstUser->syncDueDateReminders($firstUser->id);

    $dueTaskForSecondUser = Task::factory()->create([
        'user_id' => $secondUser->id,
        'due_date' => now()->addDay()->toDateString(),
        'reminder_days' => [1],
    ]);
    $dueTaskForSecondUser->syncDueDateReminders($secondUser->id);

    $this->actingAs($firstUser)
        ->post(route('inbox.reminders.read-all'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($firstUser->fresh()->unreadTaskDueDateReminders()->count())->toBe(0);
    expect($firstUser->fresh()->taskDueDateReminders()->whereNull('read_at')->count())->toBe(1); // future reminder blijft unread
    expect($secondUser->fresh()->unreadTaskDueDateReminders()->count())->toBe(1);
});

test('user can open reminders tab in inbox center and only due reminders are shown', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();

    $dueTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Deadline morgen',
        'due_date' => now()->addDay()->toDateString(),
        'reminder_days' => [1],
    ]);

    $futureTask = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Deadline later',
        'due_date' => now()->addDays(14)->toDateString(),
        'reminder_days' => [7],
    ]);

    $dueTask->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);
    $futureTask->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $dueTask->syncDueDateReminders($owner->id);
    $futureTask->syncDueDateReminders($owner->id);

    $dueReminder = TaskDueDateReminder::query()
        ->where('task_id', $dueTask->id)
        ->where('user_id', $collaborator->id)
        ->firstOrFail();

    $futureReminder = TaskDueDateReminder::query()
        ->where('task_id', $futureTask->id)
        ->where('user_id', $collaborator->id)
        ->firstOrFail();

    $this->actingAs($collaborator)
        ->get(route('inbox.index', ['tab' => 'reminders', 'reminder' => 'unread']))
        ->assertOk()
        ->assertSee(__('ui.reminders'))
        ->assertSee(__('ui.invite_for_task', ['title' => $dueTask->title]))
        ->assertSee(route('inbox.reminders.open', $dueReminder), false)
        ->assertDontSee(__('ui.invite_for_task', ['title' => $futureTask->title]))
        ->assertDontSee(route('inbox.reminders.open', $futureReminder), false);
});

test('duplicate reminder days are normalized to unique values and create unique reminder rows', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('task.store'), [
            'title' => 'Duplicate reminders task',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => now()->addDays(12)->toDateString(),
            'reminder_days' => [7, 7, 3, 1, 3, 1],
        ])
        ->assertRedirect(route('task.index'));

    $task = Task::query()->latest('id')->firstOrFail();

    expect($task->reminder_days)->toBe([1, 3, 7]);
    expect(TaskDueDateReminder::query()
        ->where('task_id', $task->id)
        ->where('user_id', $owner->id)
        ->count())->toBe(3);
});

test('invalid reminder day values are rejected by validation', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->from(route('task.index'))
        ->post(route('task.store'), [
            'title' => 'Invalid reminders task',
            'description' => 'Omschrijving',
            'status' => 'pending',
            'due_date' => now()->addDays(10)->toDateString(),
            'reminder_days' => [7, 366],
        ])
        ->assertRedirect(route('task.index'))
        ->assertSessionHasErrors(['reminder_days.1']);
});

test('create modal keeps reminders hidden until due date is filled', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get(route('task.index'))
        ->assertOk()
        ->assertSee("x-model=\"dueDate\"", false)
        ->assertSee("x-show=\"String(dueDate ?? '').trim() !== ''\"", false);
});

test('reminder scheduling migration can be rerun safely after partial state', function () {
    $migration = require database_path('migrations/2026_03_04_141000_add_schedule_columns_to_task_due_date_reminders_table.php');

    expect(fn () => $migration->up())->not->toThrow(\Throwable::class);
    expect(fn () => $migration->up())->not->toThrow(\Throwable::class);

    expect(Schema::hasColumns('task_due_date_reminders', ['days_before', 'remind_on_date']))->toBeTrue();
});
