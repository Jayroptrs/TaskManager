<?php

namespace App\Actions;

use App\Models\Task;
use App\TaskStatus;
use Illuminate\Support\Facades\DB;

class UpdateTask
{
    public function handle(array $attributes, Task $task)
    {
        $data = collect($attributes)->only([
            'title', 'description', 'status', 'links', 'tags'
        ])->toArray();

        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);

        if ($attributes['image'] ?? false) {
            $data['image_path'] = $attributes['image']->store('ideas', 'public');
        }

        $steps = collect($attributes['steps'] ?? [])
            ->map(function ($step) use ($data) {
                $normalizedStep = [
                    'description' => (string) ($step['description'] ?? ''),
                    'completed' => (bool) ($step['completed'] ?? false),
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
}
