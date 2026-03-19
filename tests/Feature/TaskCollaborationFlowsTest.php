<?php

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskActivityLog;
use App\Models\TaskInvite;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('invite by email rejects user who is already a collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => $collaborator->email])
        ->assertSessionHasErrors('email');

    expect(TaskCollaborationRequest::query()
        ->where('task_id', $task->id)
        ->where('invitee_id', $collaborator->id)
        ->count())->toBe(0);
});

test('invite by email validates invalid email format', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('owner can create invite link with creator and expiry metadata', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertRedirect();

    $invite = TaskInvite::query()->latest('id')->first();

    expect($invite)->not->toBeNull();
    expect($invite->task_id)->toBe($task->id);
    expect($invite->created_by)->toBe($owner->id);
    expect($invite->token)->not->toBe('');
    expect($invite->expires_at)->not->toBeNull();
});

test('creating a new invite link shows that previous invite was invalidated', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    TaskInvite::issue($task, $owner, now()->addDays(5));

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertRedirect()
        ->assertSessionHas('success', __('messages.invite_link_created_previous_invalidated'));
});

test('creating a new invite link invalidates the previous active invite link', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $firstInvite = TaskInvite::issue($task, $owner, now()->addDays(5));
    $secondInvite = TaskInvite::issue($task, $owner, now()->addDays(5));

    expect($firstInvite->fresh()->isExpired())->toBeTrue();
    expect($secondInvite->fresh()->isExpired())->toBeFalse();

    $this->actingAs($invitee)
        ->get(route('task.invites.accept', $firstInvite->token))
        ->assertRedirect(route('task.index'))
        ->assertSessionHasErrors('invite');

    $this->actingAs($invitee)
        ->get(route('task.invites.accept', $secondInvite->token))
        ->assertRedirect(route('task.show', $task));
});

test('collaborator cannot create invite link', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.invites.link', $task))
        ->assertForbidden();
});

test('guest cannot access collaboration mutation routes', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $request = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => User::factory()->create()->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->post(route('task.collaborators.email', $task), ['email' => 'x@example.com'])
        ->assertRedirect(route('login'));
    $this->post(route('task.invites.link', $task))
        ->assertRedirect(route('login'));
    $this->post(route('task.collab-requests.accept', $request))
        ->assertRedirect(route('login'));
    $this->post(route('task.collab-requests.reject', $request))
        ->assertRedirect(route('login'));
});

test('accepting invite link updates usage counters and collaborator access', function () {
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
        'added_by' => $owner->id,
    ]);

    $freshInvite = $invite->fresh();
    expect($freshInvite->accepted_count)->toBe(1);
    expect($freshInvite->last_used_at)->not->toBeNull();
});

test('accepting same invite link twice does not duplicate collaborator row', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $invite = TaskInvite::issue($task, $owner);

    $this->actingAs($invitee)->get(route('task.invites.accept', $invite->token));
    $this->actingAs($invitee)->get(route('task.invites.accept', $invite->token));

    expect($task->collaborators()->whereKey($invitee->id)->count())->toBe(1);
    expect($invite->fresh()->accepted_count)->toBe(2);
});

test('owner accepting invite link does not add owner as collaborator', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $invite = TaskInvite::issue($task, $owner);

    $this->actingAs($owner)
        ->get(route('task.invites.accept', $invite->token))
        ->assertRedirect(route('task.show', $task));

    expect($task->collaborators()->whereKey($owner->id)->exists())->toBeFalse();
    expect($invite->fresh()->accepted_count)->toBe(1);
});

test('rejecting collaboration request marks request rejected and does not grant access', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $request = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.reject', $request))
        ->assertRedirect();

    $this->assertDatabaseHas('task_collaboration_requests', [
        'id' => $request->id,
        'status' => TaskCollaborationRequest::STATUS_REJECTED,
    ]);

    expect($request->fresh()->responded_at)->not->toBeNull();
    expect($task->collaborators()->whereKey($invitee->id)->exists())->toBeFalse();

    $this->actingAs($invitee)
        ->get(route('task.show', $task))
        ->assertForbidden();
});

test('removed collaborator immediately loses access to task', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('task.collaborators.destroy', [$task, $collaborator]))
        ->assertRedirect();

    $this->actingAs($collaborator)
        ->get(route('task.show', $task))
        ->assertForbidden();
});

test('task manage modal shows collaborator email next to collaborator name', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create([
        'name' => 'Test Samenwerker',
        'email' => 'test.samenwerker@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('task.show', $task))
        ->assertOk()
        ->assertSee('Test Samenwerker')
        ->assertSee('test.samenwerker@example.com');
});

test('invite by email records collaboration request activity log', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee-log@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.collaborators.email', $task), ['email' => $invitee->email])
        ->assertRedirect();

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $owner->id,
        'action' => 'collab_request_sent',
    ]);

    $log = TaskActivityLog::query()
        ->where('idea_id', $task->id)
        ->where('action', 'collab_request_sent')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->metadata['invitee'] ?? null)->toBe($invitee->name);
});

test('create invite link records activity log', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertRedirect();

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $owner->id,
        'action' => 'invite_link_created',
    ]);
});

test('accept request records accepted activity log', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $request = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.accept', $request))
        ->assertRedirect();

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $invitee->id,
        'action' => 'collab_request_accepted',
    ]);
});

test('reject request records rejected activity log', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $request = TaskCollaborationRequest::create([
        'task_id' => $task->id,
        'inviter_id' => $owner->id,
        'invitee_id' => $invitee->id,
        'status' => TaskCollaborationRequest::STATUS_PENDING,
    ]);

    $this->actingAs($invitee)
        ->post(route('task.collab-requests.reject', $request))
        ->assertRedirect();

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $invitee->id,
        'action' => 'collab_request_rejected',
    ]);
});

test('removing collaborator records activity log with collaborator metadata', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('task.collaborators.destroy', [$task, $collaborator]))
        ->assertRedirect();

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $owner->id,
        'action' => 'collab_removed',
    ]);

    $log = TaskActivityLog::query()
        ->where('idea_id', $task->id)
        ->where('action', 'collab_removed')
        ->latest('id')
        ->first();

    expect($log->metadata['collaborator'] ?? null)->toBe($collaborator->name);
});

test('leaving task records activity log with collaborator metadata', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($collaborator)
        ->post(route('task.leave', $task))
        ->assertRedirect(route('task.index'));

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $collaborator->id,
        'action' => 'collab_left',
    ]);

    $log = TaskActivityLog::query()
        ->where('idea_id', $task->id)
        ->where('action', 'collab_left')
        ->latest('id')
        ->first();

    expect($log->metadata['collaborator'] ?? null)->toBe($collaborator->name);
});

test('accept invite link records added via link activity log', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $invite = TaskInvite::issue($task, $owner);

    $this->actingAs($invitee)
        ->get(route('task.invites.accept', $invite->token))
        ->assertRedirect(route('task.show', $task));

    $this->assertDatabaseHas('task_activity_logs', [
        'idea_id' => $task->id,
        'actor_id' => $invitee->id,
        'action' => 'collab_added_via_link',
    ]);
});

test('collaboration actions are rate limited after configured burst', function () {
    RateLimiter::for('collaboration-actions', fn (Request $request) => [
        Limit::perMinute(2)->by('test-collab:'.$request->user()?->id),
    ]);

    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertRedirect();

    $this->actingAs($owner)
        ->post(route('task.invites.link', $task))
        ->assertStatus(429);
});
