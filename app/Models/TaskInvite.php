<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaskInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'created_by',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public static function issue(Task $task, User $creator, ?\DateTimeInterface $expiresAt = null): self
    {
        self::query()
            ->where('task_id', $task->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->update(['expires_at' => now()]);

        return self::create([
            'task_id' => $task->id,
            'created_by' => $creator->id,
            'token' => Str::random(48),
            'expires_at' => $expiresAt ?? now()->addDays(30),
        ]);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
