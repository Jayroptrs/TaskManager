<?php

namespace App\Models;

use App\TaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $table = 'ideas';

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'reminder_days',
        'tags',
        'links',
        'image_path',
    ];

    protected $casts = [
        'links' => 'array',
        'tags' => 'array',
        'status' => TaskStatus::class,
        'due_date' => 'date',
        'reminder_days' => 'array',
    ];

    protected $attributes = [
        'status' => TaskStatus::PENDING->value,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Task $task): void {
            if ($task->hasUploadedImage()) {
                Storage::disk('public')->delete($task->image_path);
            }

            $task->comments()
                ->whereNull('parent_comment_id')
                ->get()
                ->each
                ->delete();
        });
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function hasUploadedImage(): bool
    {
        return ! empty($this->image_path) && ! str_starts_with($this->image_path, 'images/');
    }

    public static function statusCounts(User $user): Collection
    {
        $counts = static::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(TaskStatus::cases())
            ->mapWithKeys(fn ($status) => [
                $status->value => $counts->get($status->value, 0),
            ])
            ->put('all', static::query()->visibleTo($user)->count());
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user) {
            $builder
                ->where('user_id', $user->id)
                ->orWhereHas('collaborators', fn (Builder $q) => $q->where('users.id', $user->id));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class, 'idea_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'idea_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_collaborators', 'idea_id', 'user_id')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TaskInvite::class, 'task_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TaskActivityLog::class, 'idea_id');
    }

    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(TaskCollaborationRequest::class, 'task_id');
    }

    public function dueDateReminders(): HasMany
    {
        return $this->hasMany(TaskDueDateReminder::class, 'task_id');
    }

    public function recordActivity(string $action, ?int $actorId = null, array $metadata = []): void
    {
        $this->activityLogs()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function syncDueDateReminders(?int $triggeredByUserId = null): void
    {
        if ($this->due_date === null) {
            $this->dueDateReminders()->delete();

            return;
        }

        $daysBefore = $this->normalizedReminderDays();
        if ($daysBefore->isEmpty()) {
            $this->dueDateReminders()->delete();

            return;
        }

        $dueDate = $this->due_date->toDateString();
        $today = today();
        $participantIds = $this->participantUserIds();
        $existing = $this->dueDateReminders()->get();
        $expectedKeys = [];

        foreach ($participantIds as $participantId) {
            foreach ($daysBefore as $day) {
                $remindOnDate = $this->due_date->copy()->subDays($day);
                if ($remindOnDate->lt($today)) {
                    continue;
                }

                $key = $participantId.':'.$day;
                $expectedKeys[] = $key;

                /** @var TaskDueDateReminder|null $reminder */
                $reminder = $existing->first(
                    fn (TaskDueDateReminder $item) => $item->user_id === $participantId && (int) $item->days_before === $day
                );
                $remindOnDateString = $remindOnDate->toDateString();

                if (! $reminder) {
                    $this->dueDateReminders()->create([
                        'user_id' => $participantId,
                        'created_by_user_id' => $triggeredByUserId,
                        'due_date' => $dueDate,
                        'days_before' => $day,
                        'remind_on_date' => $remindOnDateString,
                        'read_at' => null,
                    ]);

                    continue;
                }

                if (
                    $reminder->due_date?->toDateString() !== $dueDate
                    || $reminder->remind_on_date?->toDateString() !== $remindOnDateString
                ) {
                    $reminder->update([
                        'created_by_user_id' => $triggeredByUserId,
                        'due_date' => $dueDate,
                        'remind_on_date' => $remindOnDateString,
                        'read_at' => null,
                    ]);
                }
            }
        }

        $idsToDelete = $existing
            ->reject(function (TaskDueDateReminder $reminder) use ($expectedKeys) {
                return in_array($reminder->user_id.':'.(int) $reminder->days_before, $expectedKeys, true);
            })
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            $this->dueDateReminders()->whereIn('id', $idsToDelete->all())->delete();
        }
    }

    public function formattedDescription(): Attribute
    {
        return Attribute::get(
            fn($value, $attributes) => str($attributes['description'] ?? '')->markdown([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])
        );
    }

    private function participantUserIds(): Collection
    {
        return $this->collaborators()
            ->pluck('users.id')
            ->push($this->user_id)
            ->unique()
            ->values();
    }

    private function normalizedReminderDays(): Collection
    {
        return collect($this->reminder_days ?? [7, 3, 1])
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0)
            ->unique()
            ->sortDesc()
            ->values();
    }
}
