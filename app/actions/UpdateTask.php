<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Task;
use App\TaskStatus;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateTask
{
    public function __construct(#[CurrentUser] protected User $user)
    {
        //
    }

    public function handle(array $attributes, Task $task)
    {
        $data = collect($attributes)->only([
            'title', 'description', 'status', 'due_date', 'links', 'tags'
        ])->toArray();

        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        $remindersEnabled = (bool) ($attributes['reminders_enabled'] ?? true);
        $data['reminder_days'] = $this->normalizeReminderDays(
            $attributes['reminder_days'] ?? null,
            $remindersEnabled,
            $task->reminder_days ?? [7, 3, 1]
        );

        $newImagePath = null;
        $oldImagePath = null;
        if ($attributes['image'] ?? false) {
            $newImagePath = $attributes['image']->store('ideas', 'public');
            $oldImagePath = $task->image_path;
            $data['image_path'] = $newImagePath;
        }

        $allowedAssigneeIds = $task->collaborators()
            ->pluck('users.id')
            ->push($task->user_id)
            ->unique()
            ->values()
            ->all();

        $steps = collect($attributes['steps'] ?? [])
            ->map(function ($step) use ($data, $allowedAssigneeIds) {
                $assignedUserId = isset($step['assigned_user_id']) ? (int) $step['assigned_user_id'] : null;

                $normalizedStep = [
                    'description' => (string) ($step['description'] ?? ''),
                    'completed' => (bool) ($step['completed'] ?? false),
                    'assigned_user_id' => in_array($assignedUserId, $allowedAssigneeIds, true) ? $assignedUserId : null,
                ];

                if (($data['status'] ?? null) === TaskStatus::COMPLETED->value) {
                    $normalizedStep['completed'] = true;
                }

                return $normalizedStep;
            })
            ->all();

        DB::transaction(function () use ($task, $data, $steps) {
            $task->update($data);

            $task->steps()->delete();
            $task->steps()->createMany($steps);
        });

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => ltrim(strtolower(trim((string) $tag)), '#'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeReminderDays(?array $days, bool $enabled, array $fallback): array
    {
        if (! $enabled) {
            return [];
        }

        $normalized = collect($days ?? $fallback)
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $normalized === [] ? $fallback : $normalized;
    }
}
