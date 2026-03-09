<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['mentions', 'invites', 'reminders'], true)
            ? $request->query('tab')
            : 'mentions';
        $mentionFilter = in_array($request->query('mention'), ['unread', 'all'], true)
            ? $request->query('mention')
            : 'unread';
        $reminderFilter = in_array($request->query('reminder'), ['unread', 'all'], true)
            ? $request->query('reminder')
            : 'unread';

        $mentionsQuery = $user->taskCommentMentions()
            ->with(['task:id,title', 'mentionedBy:id,name', 'comment:id,idea_id,body'])
            ->latest();

        if ($mentionFilter === 'unread') {
            $mentionsQuery->whereNull('read_at');
        }

        $invitesQuery = $user->incomingCollaborationRequests()
            ->pending()
            ->with(['task:id,title', 'inviter:id,name'])
            ->latest();

        $remindersQuery = $user->dueTaskDueDateReminders()
            ->with(['task:id,title'])
            ->latest();

        if ($reminderFilter === 'unread') {
            $remindersQuery->whereNull('read_at');
        }

        return view('inbox.index', [
            'activeTab' => $tab,
            'mentionFilter' => $mentionFilter,
            'reminderFilter' => $reminderFilter,
            'mentions' => $tab === 'mentions' ? $mentionsQuery->paginate(20)->withQueryString() : null,
            'invites' => $tab === 'invites' ? $invitesQuery->paginate(20)->withQueryString() : null,
            'reminders' => $tab === 'reminders' ? $remindersQuery->paginate(20)->withQueryString() : null,
            'pendingInviteCount' => $user->incomingCollaborationRequests()->pending()->count(),
            'unreadMentionCount' => $user->unreadTaskCommentMentions()->count(),
            'unreadReminderCount' => $user->unreadTaskDueDateReminders()->count(),
        ]);
    }

    public function markAllMentionsRead(Request $request)
    {
        $request->user()
            ->taskCommentMentions()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', __('messages.inbox_mentions_marked_read'));
    }

    public function markAllRemindersRead(Request $request)
    {
        $request->user()
            ->dueTaskDueDateReminders()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', __('messages.inbox_reminders_marked_read'));
    }
}
