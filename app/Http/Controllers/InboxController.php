<?php

namespace App\Http\Controllers;

use App\Models\SupportMessageReply;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['mentions', 'invites', 'reminders', 'support'], true)
            ? $request->query('tab')
            : 'mentions';
        $mentionFilter = in_array($request->query('mention'), ['unread', 'all'], true)
            ? $request->query('mention')
            : 'unread';
        $reminderFilter = in_array($request->query('reminder'), ['unread', 'all'], true)
            ? $request->query('reminder')
            : 'unread';
        $supportFilter = in_array($request->query('support'), ['unread', 'all'], true)
            ? $request->query('support')
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

        $supportRepliesQuery = $user->supportRepliesForOwnTickets()
            ->where('support_message_replies.is_admin', true)
            ->with([
                'supportMessage:id,user_id,subject',
                'user:id,name',
            ])
            ->latest();

        if ($supportFilter === 'unread') {
            $supportRepliesQuery->whereNull('support_message_replies.read_at');
        }

        return view('inbox.index', [
            'activeTab' => $tab,
            'mentionFilter' => $mentionFilter,
            'reminderFilter' => $reminderFilter,
            'supportFilter' => $supportFilter,
            'mentions' => $tab === 'mentions' ? $mentionsQuery->paginate(20)->withQueryString() : null,
            'invites' => $tab === 'invites' ? $invitesQuery->paginate(20)->withQueryString() : null,
            'reminders' => $tab === 'reminders' ? $remindersQuery->paginate(20)->withQueryString() : null,
            'supportReplies' => $tab === 'support' ? $supportRepliesQuery->paginate(20)->withQueryString() : null,
            'pendingInviteCount' => $user->incomingCollaborationRequests()->pending()->count(),
            'unreadMentionCount' => $user->unreadTaskCommentMentions()->count(),
            'unreadReminderCount' => $user->unreadTaskDueDateReminders()->count(),
            'unreadSupportReplyCount' => $user->unreadSupportReplies()->count(),
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

    public function markAllSupportRead(Request $request)
    {
        $request->user()
            ->unreadSupportReplies()
            ->update(['read_at' => now()]);

        return back()->with('success', __('messages.inbox_support_marked_read'));
    }

    public function openSupportReply(Request $request, SupportMessageReply $reply)
    {
        $supportMessage = $reply->supportMessage;

        abort_unless($supportMessage !== null, 404);
        abort_unless($supportMessage->user_id === $request->user()->id, 403);
        abort_unless($reply->is_admin, 403);

        $request->user()
            ->unreadSupportReplies()
            ->where('support_message_replies.support_message_id', $supportMessage->id)
            ->update(['support_message_replies.read_at' => now()]);

        return redirect()->route('support', ['ticket' => $supportMessage->id]);
    }
}
