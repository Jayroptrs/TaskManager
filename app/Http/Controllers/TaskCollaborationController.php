<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\TaskInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskCollaborationController extends Controller
{
    public function inviteByEmail(Request $request, Task $task)
    {
        Gate::authorize('manageCollaborators', $task);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $collaborator = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $collaborator) {
            return back()->withErrors([
                'email' => __('task.collab_email_not_found'),
            ]);
        }

        if ($task->user->is($collaborator)) {
            return back()->withErrors([
                'email' => __('task.collab_owner_already'),
            ]);
        }

        if ($task->collaborators()->whereKey($collaborator->id)->exists()) {
            return back()->withErrors([
                'email' => __('task.collab_already_added'),
            ]);
        }

        $existsPending = TaskCollaborationRequest::query()
            ->where('task_id', $task->id)
            ->where('invitee_id', $collaborator->id)
            ->where('status', TaskCollaborationRequest::STATUS_PENDING)
            ->exists();

        if ($existsPending) {
            return back()->withErrors([
                'email' => __('task.collab_invite_pending'),
            ]);
        }

        TaskCollaborationRequest::create([
            'task_id' => $task->id,
            'inviter_id' => $request->user()->id,
            'invitee_id' => $collaborator->id,
            'status' => TaskCollaborationRequest::STATUS_PENDING,
        ]);
        $task->recordActivity('collab_request_sent', $request->user()->id, [
            'invitee' => $collaborator->name,
        ]);

        return back()->with('success', __('messages.collab_request_sent'));
    }

    public function createInviteLink(Request $request, Task $task)
    {
        Gate::authorize('manageCollaborators', $task);

        $hadActiveInvite = $task->invites()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        TaskInvite::issue($task, $request->user());
        $task->recordActivity('invite_link_created', $request->user()->id);

        return back()
            ->with('success', $hadActiveInvite
                ? __('messages.invite_link_created_previous_invalidated')
                : __('messages.invite_link_created'))
            ->with('reopen_modal', 'task-collaborators-activity');
    }

    public function acceptInvite(Request $request, string $token)
    {
        $invite = TaskInvite::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($invite->isExpired()) {
            return redirect()->route('task.index')->withErrors([
                'invite' => __('task.invite_expired'),
            ]);
        }

        $task = $invite->task;
        $user = $request->user();

        if (! $task->user->is($user)) {
            $task->collaborators()->syncWithoutDetaching([
                $user->id => ['added_by' => $invite->created_by],
            ]);
            $task->syncDueDateReminders($request->user()->id);
        }
        $task->recordActivity('collab_added_via_link', $user->id, [
            'collaborator' => $user->name,
        ]);

        $invite->increment('accepted_count');
        $invite->forceFill(['last_used_at' => now()])->save();

        return redirect()->route('task.show', $task)->with('success', __('messages.invite_accepted'));
    }

    public function removeCollaborator(Request $request, Task $task, User $user)
    {
        Gate::authorize('manageCollaborators', $task);

        if ($task->user->is($user)) {
            return back()->withErrors([
                'collaborators' => __('task.owner_cannot_be_removed'),
            ]);
        }

        $task->collaborators()->detach($user->id);
        $task->syncDueDateReminders($request->user()->id);
        $task->recordActivity('collab_removed', $request->user()->id, [
            'collaborator' => $user->name,
        ]);

        return back()->with('success', __('messages.collab_removed'));
    }

    public function leave(Request $request, Task $task)
    {
        Gate::authorize('workWith', $task);

        if ($task->user->is($request->user())) {
            return back()->withErrors([
                'collaborators' => __('task.owner_cannot_leave'),
            ]);
        }

        $task->collaborators()->detach($request->user()->id);
        $task->syncDueDateReminders($request->user()->id);
        $task->recordActivity('collab_left', $request->user()->id, [
            'collaborator' => $request->user()->name,
        ]);

        return redirect()->route('task.index')->with('success', __('messages.left_task'));
    }

    public function acceptRequest(Request $request, TaskCollaborationRequest $collaborationRequest)
    {
        abort_unless(
            $collaborationRequest->invitee_id === $request->user()->id
            && $collaborationRequest->status === TaskCollaborationRequest::STATUS_PENDING,
            403
        );

        $task = $collaborationRequest->task;

        if (! $task->user->is($request->user())) {
            $task->collaborators()->syncWithoutDetaching([
                $request->user()->id => ['added_by' => $collaborationRequest->inviter_id],
            ]);
            $task->syncDueDateReminders($request->user()->id);
        }

        $collaborationRequest->update([
            'status' => TaskCollaborationRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
        $task->recordActivity('collab_request_accepted', $request->user()->id, [
            'collaborator' => $request->user()->name,
        ]);

        return back()->with('success', __('messages.collab_request_accepted'));
    }

    public function rejectRequest(Request $request, TaskCollaborationRequest $collaborationRequest)
    {
        abort_unless(
            $collaborationRequest->invitee_id === $request->user()->id
            && $collaborationRequest->status === TaskCollaborationRequest::STATUS_PENDING,
            403
        );

        $collaborationRequest->update([
            'status' => TaskCollaborationRequest::STATUS_REJECTED,
            'responded_at' => now(),
        ]);
        $collaborationRequest->task->recordActivity('collab_request_rejected', $request->user()->id, [
            'collaborator' => $request->user()->name,
        ]);

        return back()->with('success', __('messages.collab_request_rejected'));
    }
}
