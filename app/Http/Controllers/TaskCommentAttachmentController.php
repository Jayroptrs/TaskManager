<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskCommentAttachmentController extends Controller
{
    public function download(Task $task, TaskComment $comment, TaskCommentAttachment $attachment)
    {
        Gate::authorize('workWith', $task);

        abort_unless(
            $comment->idea_id === $task->id
            && $attachment->task_comment_id === $comment->id,
            404
        );

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }
}
