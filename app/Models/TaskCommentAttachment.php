<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskCommentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_comment_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TaskCommentAttachment $attachment): void {
            if (! empty($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
        });
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'task_comment_id');
    }
}
