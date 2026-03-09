<?php

namespace App\Http\Controllers;

use App\Models\TaskCommentMention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskMentionController extends Controller
{
    public function open(Request $request, TaskCommentMention $mention)
    {
        abort_unless($mention->mentioned_user_id === $request->user()->id, 403);

        Gate::authorize('workWith', $mention->task);

        if ($mention->read_at === null) {
            $mention->update(['read_at' => now()]);
        }

        return redirect()->to(route('task.show', $mention->task).'#comment-'.$mention->task_comment_id);
    }
}
