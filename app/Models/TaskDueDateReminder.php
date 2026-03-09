<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDueDateReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'created_by_user_id',
        'due_date',
        'days_before',
        'remind_on_date',
        'read_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'days_before' => 'integer',
        'remind_on_date' => 'date',
        'read_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
