<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentMention;
use App\Support\CommentMentionParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task, CommentMentionParser $mentionParser)
    {
        Gate::authorize('workWith', $task);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:3000'],
            'parent_comment_id' => ['nullable', 'integer', 'exists:task_comments,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,txt,csv,doc,docx,xls,xlsx,zip'],
        ]);

        $parentCommentId = null;
        if (! empty($validated['parent_comment_id'])) {
            $parentComment = $task->comments()->whereKey((int) $validated['parent_comment_id'])->first();
            if (! $parentComment) {
                return back()->withErrors([
                    'parent_comment_id' => __('task.reply_parent_invalid'),
                ]);
            }
            $parentCommentId = $parentComment->id;
        }

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['comment'],
            'parent_comment_id' => $parentCommentId,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $comment->attachments()->create([
                'path' => $file->store('comment-attachments', 'public'),
                'original_name' => (string) $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $mentionedUsers = $mentionParser->mentionedUsers($validated['comment'], $task, $request->user());
        foreach ($mentionedUsers as $user) {
            TaskCommentMention::query()->firstOrCreate([
                'task_comment_id' => $comment->id,
                'mentioned_user_id' => $user->id,
            ], [
                'task_id' => $task->id,
                'mentioned_by_user_id' => $request->user()->id,
                'read_at' => null,
            ]);
        }

        $task->recordActivity('comment_added', $request->user()->id);

        return back()->with('success', $parentCommentId
            ? __('messages.comment_reply_added')
            : __('messages.comment_added'));
    }

    public function destroy(Request $request, Task $task, TaskComment $comment)
    {
        Gate::authorize('workWith', $task);

        abort_unless(
            $comment->idea_id === $task->id
            && ($comment->user_id === $request->user()->id || $task->user_id === $request->user()->id),
            403
        );

        $comment->delete();
        $task->recordActivity('comment_deleted', $request->user()->id);

        return back()->with('success', __('messages.comment_deleted'));
    }
}
