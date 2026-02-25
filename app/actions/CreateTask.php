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
            'title', 'description', 'status', 'links', 'tags'
        ])->toArray();

        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);

        if ($attributes['image'] ?? false) {
            $data['image_path'] = $attributes['image']->store('ideas', 'public');
        }

        DB::transaction(function () use ($data, $attributes) {
            $task = $this->user->tasks()->create($data);

            $task->steps()->createMany($attributes['steps'] ?? []);
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
