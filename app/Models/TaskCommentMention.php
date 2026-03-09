<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCommentMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_comment_id',
        'task_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'task_comment_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by_user_id');
    }
}
