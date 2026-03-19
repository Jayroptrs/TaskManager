<?php

namespace App\Models;

use App\TaskPriority;
use App\TaskStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\UserAuditLog;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $table = 'ideas';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'archived_at',
        'reminder_days',
        'tags',
        'links',
        'image_path',
    ];

    protected $casts = [
        'links' => 'array',
        'tags' => 'array',
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'date',
        'archived_at' => 'datetime',
        'reminder_days' => 'array',
    ];

    protected $attributes = [
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
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

    public static function statusCounts(User $user, string $archive = 'active'): Collection
    {
        $counts = static::query()
            ->visibleTo($user)
            ->applyArchiveState($archive)
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

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeApplyArchiveState(Builder $query, string $archive): Builder
    {
        return match ($archive) {
            'archived' => $query->archived(),
            'all' => $query,
            default => $query->notArchived(),
        };
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
        $auditMetadata = array_merge($metadata, [
            'task_id' => $this->id,
            'task_title' => $this->title,
            'task_owner_id' => $this->user_id,
        ]);

        $this->activityLogs()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        if ($actorId !== null) {
            UserAuditLog::query()->create([
                'target_user_id' => $actorId,
                'actor_user_id' => $actorId,
                'action' => $action,
                'metadata' => $auditMetadata,
                'created_at' => now(),
            ]);
        }
    }

    public function activitySnapshot(): array
    {
        return [
            'title' => (string) ($this->title ?? ''),
            'description' => (string) ($this->description ?? ''),
            'status' => $this->status?->value ?? (string) $this->getRawOriginal('status'),
            'priority' => $this->priority?->value ?? (string) $this->getRawOriginal('priority'),
            'due_date' => $this->due_date?->toDateString(),
            'tags' => collect($this->tags ?? [])->values()->all(),
            'links' => collect($this->links ?? [])->values()->all(),
            'reminder_days' => $this->normalizedReminderDays()->values()->all(),
        ];
    }

    public function activityChangesFrom(array $before): array
    {
        $after = $this->activitySnapshot();
        $supportedFields = ['title', 'description', 'status', 'priority', 'due_date', 'tags', 'links', 'reminder_days'];
        $changes = [];

        foreach ($supportedFields as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue = $after[$field] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $changes[$field] = [
                'from' => $beforeValue,
                'to' => $afterValue,
            ];
        }

        return $changes;
    }

    public static function activityChangeEntries(?array $metadata): Collection
    {
        return collect(data_get($metadata, 'changes', []))
            ->map(function (array $change, string $field) {
                return [
                    'field' => $field,
                    'label' => __('task.activity_field_'.$field),
                    'from' => static::formatActivityFieldValue($field, $change['from'] ?? null),
                    'to' => static::formatActivityFieldValue($field, $change['to'] ?? null),
                ];
            })
            ->values();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
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

    private static function formatActivityFieldValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return __('task.activity_value_empty');
        }

        return match ($field) {
            'status' => TaskStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            'priority' => TaskPriority::tryFrom((string) $value)?->label() ?? (string) $value,
            'due_date' => static::formatActivityDateValue($value),
            'tags', 'links', 'reminder_days' => collect(is_array($value) ? $value : [$value])
                ->map(fn ($item) => (string) $item)
                ->filter()
                ->implode(', '),
            'description' => Str::limit(trim((string) $value), 120),
            default => (string) $value,
        };
    }

    private static function formatActivityDateValue(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->translatedFormat('j M Y');
        }

        try {
            return Carbon::parse((string) $value)->translatedFormat('j M Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
