<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskImageController extends Controller
{
    public function destroy(Task $task)
    {
        Gate::authorize('workWith', $task);

        Storage::disk('public')->delete($task->image_path);
        
        $task->update(['image_path' => null]);

        return back();
    }
}
