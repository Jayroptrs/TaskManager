<?php

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskInvite;
use App\Models\User;

test('only owner can invite collaborator by email', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.collaborators.email', $task), ['email' => 'x@example.com'])
        ->assertForbidden();
});

test('invite by email returns error for unknown user', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => 'unknown@example.com'])
        ->assertSessionHasErrors('email');
});

test('invite by email rejects inviting owner and duplicate pending requests', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => $owner->email])
        ->assertSessionHasErrors('email');

    TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => $invitee->email])
        ->assertSessionHasErrors('email');
});

test('expired invite link cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $invite = TaskInvite::issue($task, $owner, now()->subHour());

    $this->actingAs($invitee)
        ->get(route('task.invites.accept', $invite->token))
        ->assertRedirect(route('task.index'))
        ->assertSessionHasErrors('invite');
});

test('owner cannot remove self from collaborators endpoint', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('task.collaborators.destroy', [$task, $owner]))
        ->assertSessionHasErrors('collaborators');
});

test('owner cannot leave own task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.leave', $task))
        ->assertSessionHasErrors('collaborators');
});

test('only invitee can accept or reject collaboration request', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $request = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($otherUser)
        ->post(route('task.collab-requests.accept', $request))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->post(route('task.collab-requests.reject', $request))
        ->assertForbidden();
});

test('cannot accept or reject non pending collaboration request', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $acceptedRequest = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_ACCEPTED,
    ]);

    $rejectedRequest = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_REJECTED,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.accept', $acceptedRequest))
        ->assertForbidden();

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.reject', $rejectedRequest))
        ->assertForbidden();
});

