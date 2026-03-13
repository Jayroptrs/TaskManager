<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskImageController extends Controller
{
    public function destroy(Task $task)
    {
        Gate::authorize('manageTask', $task);

        if (! $task->hasUploadedImage()) {
            return back()->with('error', __('messages.task_default_image_locked'));
        }

        Storage::disk('public')->delete($task->image_path);

        $defaultImages = collect(config('tasks.default_images', []))
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values();
        $fallbackImage = $defaultImages->isNotEmpty() ? $defaultImages->random() : null;

        $task->update(['image_path' => $fallbackImage]);

        return back()->with('success', __('messages.task_image_reset_to_default'));
    }
}
