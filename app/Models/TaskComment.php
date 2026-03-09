<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'user_id',
        'body',
        'parent_comment_id',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TaskComment $comment): void {
            $comment->attachments()->get()->each->delete();
            $comment->replies()->get()->each->delete();
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(TaskCommentMention::class, 'task_comment_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskCommentAttachment::class, 'task_comment_id');
    }
}
