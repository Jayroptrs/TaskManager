<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\Task;
use App\Models\User;
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

    public function resolve(SupportMessage $supportMessage)
    {
        $this->applySupportStatus($supportMessage, SupportMessage::STATUS_RESOLVED);

        return back()->with('success', __('messages.support_status_updated'));
    }

    public function updateSupportStatus(Request $request, SupportMessage $supportMessage)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(SupportMessage::statuses())],
        ]);

        $this->applySupportStatus($supportMessage, $validated['status']);

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

    public function userTasks(User $user)
    {
        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->withCount('collaborators')
            ->latest()
            ->paginate(18);

        return view('admin.user-tasks', [
            'targetUser' => $user,
            'tasks' => $tasks,
        ]);
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
