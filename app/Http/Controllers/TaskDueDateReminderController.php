<?php

namespace App\Http\Controllers;

use App\Models\TaskDueDateReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskDueDateReminderController extends Controller
{
    public function open(Request $request, TaskDueDateReminder $reminder)
    {
        abort_unless($reminder->user_id === $request->user()->id, 403);
        abort_if($reminder->remind_on_date?->isFuture(), 404);

        Gate::authorize('workWith', $reminder->task);

        if ($reminder->read_at === null) {
            $reminder->update(['read_at' => now()]);
        }

        return redirect()->to(route('task.show', $reminder->task).'#task-deadline');
    }
}
