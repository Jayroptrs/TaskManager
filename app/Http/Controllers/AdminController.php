<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::query()->count();
        $totalTasks = Task::query()->count();
        $completedTasks = Task::query()->where('status', 'completed')->count();
        $openSupportCount = SupportMessage::query()->incoming()->count();
        $resolvedSupportCount = SupportMessage::query()->fullyResolved()->count();

        $recentUsers = User::query()
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'email', 'created_at']);

        $memberSupportMessages = SupportMessage::query()
            ->whereNotNull('user_id')
            ->incoming()
            ->with(['user:id,name,email', 'replies.user:id,name,email'])
            ->latest()
            ->take(40)
            ->get();

        $guestSupportMessages = SupportMessage::query()
            ->whereNull('user_id')
            ->incoming()
            ->with(['replies.user:id,name,email'])
            ->latest()
            ->take(40)
            ->get();

        $resolvedSupportMessages = SupportMessage::query()
            ->fullyResolved()
            ->with(['user:id,name,email', 'replies.user:id,name,email'])
            ->latest()
            ->take(80)
            ->get();

        return view('admin.index', [
            'totalUsers' => $totalUsers,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'openSupportCount' => $openSupportCount,
            'resolvedSupportCount' => $resolvedSupportCount,
            'recentUsers' => $recentUsers,
            'memberSupportMessages' => $memberSupportMessages,
            'guestSupportMessages' => $guestSupportMessages,
            'resolvedSupportMessages' => $resolvedSupportMessages,
        ]);
    }

    public function resolve(Request $request, SupportMessage $supportMessage)
    {
        $previousStatus = $supportMessage->status;
        $this->applySupportStatus($supportMessage, SupportMessage::STATUS_RESOLVED);
        $supportMessage->refresh();

        $this->recordUserAudit(
            $supportMessage->user,
            $request->user(),
            'admin_support_status_updated',
            [
                'support_message_id' => $supportMessage->id,
                'support_subject' => $supportMessage->subject,
                'previous_status' => $previousStatus,
                'new_status' => $supportMessage->status,
            ]
        );

        return back()->with('success', __('messages.support_status_updated'));
    }

    public function updateSupportStatus(Request $request, SupportMessage $supportMessage)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(SupportMessage::statuses())],
        ]);

        $previousStatus = $supportMessage->status;
        $this->applySupportStatus($supportMessage, $validated['status']);
        $supportMessage->refresh();

        $this->recordUserAudit(
            $supportMessage->user,
            $request->user(),
            'admin_support_status_updated',
            [
                'support_message_id' => $supportMessage->id,
                'support_subject' => $supportMessage->subject,
                'previous_status' => $previousStatus,
                'new_status' => $supportMessage->status,
            ]
        );

        return back()->with('success', __('messages.support_status_updated'));
    }

    public function replyToSupport(Request $request, SupportMessage $supportMessage)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'status' => ['nullable', Rule::in(SupportMessage::statuses())],
        ]);

        $supportMessage->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin' => true,
            'message' => $validated['message'],
        ]);

        $nextStatus = $validated['status'] ?? SupportMessage::STATUS_WAITING_FOR_USER;
        $this->applySupportStatus($supportMessage, $nextStatus);
        $supportMessage->refresh();

        $this->recordUserAudit(
            $supportMessage->user,
            $request->user(),
            'admin_support_reply_sent',
            [
                'support_message_id' => $supportMessage->id,
                'support_subject' => $supportMessage->subject,
                'new_status' => $supportMessage->status,
            ]
        );

        return back()->with('success', __('messages.support_reply_sent_admin'));
    }

    public function destroyUser(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser && $currentUser->is($user)) {
            return back()->withErrors(['admin' => 'Je kunt je eigen account niet verwijderen vanuit het adminpaneel.']);
        }

        if ($user->isAdmin()) {
            return back()->withErrors(['admin' => 'Admin accounts kunnen niet via dit paneel verwijderd worden.']);
        }

        $user->delete();

        return back()->with('success', 'Gebruiker is verwijderd.');
    }

    public function userTasks(Request $request, User $user)
    {
        $tasks = Task::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->with('user:id,name')
            ->withCount('collaborators')
            ->latest()
            ->paginate(18);

        $auditLogs = UserAuditLog::query()
            ->where('target_user_id', $user->id)
            ->where('actor_user_id', $user->id)
            ->whereIn('action', $this->taskAuditActions())
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->limit(25)
            ->get();

        return view('admin.user-tasks', [
            'targetUser' => $user,
            'tasks' => $tasks,
            'auditLogs' => $auditLogs,
        ]);
    }

    private function recordUserAudit(?User $targetUser, ?User $actor, string $action, array $metadata = []): void
    {
        if (! $targetUser) {
            return;
        }

        UserAuditLog::query()->create([
            'target_user_id' => $targetUser->id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }

    private function taskAuditActions(): array
    {
        return [
            'task_created',
            'task_updated',
            'task_deleted',
            'status_changed',
            'step_toggled',
            'comment_added',
            'comment_deleted',
            'collab_request_sent',
            'invite_link_created',
            'collab_added_via_link',
            'collab_removed',
            'collab_left',
            'collab_request_accepted',
            'collab_request_rejected',
        ];
    }

    private function applySupportStatus(SupportMessage $supportMessage, string $status): void
    {
        if ($supportMessage->status === $status) {
            $stableState = match ($status) {
                SupportMessage::STATUS_RESOLVED => $supportMessage->isFullyResolved(),
                SupportMessage::STATUS_WAITING_FOR_USER => $supportMessage->requiresUserResolutionConfirmation(),
                default => $supportMessage->admin_resolved_at === null
                    && $supportMessage->user_resolved_at === null
                    && $supportMessage->resolved_at === null,
            };

            if ($stableState) {
                return;
            }
        }

        if ($status === SupportMessage::STATUS_RESOLVED) {
            $adminResolvedAt = $supportMessage->admin_resolved_at ?? now();
            $isGuestTicket = $supportMessage->user_id === null;
            $userResolvedAt = $isGuestTicket ? ($supportMessage->user_resolved_at ?? now()) : $supportMessage->user_resolved_at;
            $isFullyResolved = $isGuestTicket || $userResolvedAt !== null;

            $supportMessage->update([
                'status' => $isFullyResolved
                    ? SupportMessage::STATUS_RESOLVED
                    : SupportMessage::STATUS_WAITING_FOR_USER,
                'admin_resolved_at' => $adminResolvedAt,
                'user_resolved_at' => $userResolvedAt,
                'resolved_at' => $isFullyResolved ? now() : null,
            ]);

            return;
        }

        $supportMessage->update([
            'status' => $status,
            'resolved_at' => null,
            'admin_resolved_at' => null,
            'user_resolved_at' => null,
        ]);
    }
}
