<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\DB;

class CreateTask
{
    public function __construct(#[CurrentUser] protected User $user)
    {
        //
    }

    public function handle(array $attributes)
    {
        $data = collect($attributes)->only([
            'title', 'description', 'status', 'due_date', 'links', 'tags'
        ])->toArray();

        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        $remindersEnabled = (bool) ($attributes['reminders_enabled'] ?? true);
        $data['reminder_days'] = $this->normalizeReminderDays($attributes['reminder_days'] ?? null, $remindersEnabled, [7, 3, 1]);

        if ($attributes['image'] ?? false) {
            $data['image_path'] = $attributes['image']->store('ideas', 'public');
        } else {
            $defaultImages = collect(config('tasks.default_images', []))
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->values();
            $data['image_path'] = $defaultImages->isNotEmpty() ? $defaultImages->random() : null;
        }

        return DB::transaction(function () use ($data, $attributes) {
            $task = $this->user->tasks()->create($data);

            $steps = collect($attributes['steps'] ?? [])
                ->map(function ($step) {
                    $assignedUserId = isset($step['assigned_user_id']) ? (int) $step['assigned_user_id'] : null;

                    return [
                        'description' => (string) ($step['description'] ?? ''),
                        'completed' => (bool) ($step['completed'] ?? false),
                        'assigned_user_id' => $assignedUserId === $this->user->id ? $this->user->id : null,
                    ];
                })
                ->all();

            $task->steps()->createMany($steps);

            return $task;
        });
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
