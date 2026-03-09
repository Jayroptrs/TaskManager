<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    /** @use HasFactory<\Database\Factories\StepFactory> */
    use HasFactory;

    protected $fillable = [
        'description',
        'completed',
        'assigned_user_id',
    ];

    protected $attributes = [
        'completed' => false,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'idea_id');
    }

    public function idea(): BelongsTo
    {
        return $this->task();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
