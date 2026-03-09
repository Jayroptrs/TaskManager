<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommentMentionParser
{
    public function mentionedUsers(string $body, Task $task, User $actor): Collection
    {
        $participants = collect([$task->user])
            ->merge($task->collaborators)
            ->filter()
            ->unique('id')
            ->values();

        $lookup = [];
        foreach ($participants as $user) {
            $this->mapUser($lookup, $user);
        }

        $mentions = collect()
            ->merge($this->extractBracketMentions($body))
            ->merge($this->extractTokenMentions($body))
            ->map(fn (string $value) => $this->normalize($value))
            ->filter()
            ->unique()
            ->map(fn (string $key) => $lookup[$key] ?? null)
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->values();

        return $mentions;
    }

    private function mapUser(array &$lookup, User $user): void
    {
        $variants = collect([
            (string) $user->email,
            (string) $user->name,
            str_replace(' ', '.', (string) $user->name),
            str_replace(' ', '_', (string) $user->name),
            str_replace(' ', '-', (string) $user->name),
        ])
            ->map(fn (string $value) => $this->normalize($value))
            ->filter()
            ->unique();

        foreach ($variants as $variant) {
            $lookup[$variant] = $user;
        }
    }

    private function extractBracketMentions(string $body): array
    {
        preg_match_all('/@\[([^\]]+)\]/u', $body, $matches);

        return $matches[1] ?? [];
    }

    private function extractTokenMentions(string $body): array
    {
        preg_match_all('/(^|[\s(])@([A-Za-z0-9._+\-@]+)/u', $body, $matches);

        return $matches[2] ?? [];
    }

    private function normalize(string $value): string
    {
        $ascii = Str::ascii(Str::lower(trim($value)));

        return preg_replace('/[^a-z0-9@._+\-]/', '', $ascii) ?? '';
    }
}
