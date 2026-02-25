<?php

namespace App\Models;

use App\TaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;


class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $table = 'ideas';

    protected $fillable = [
        'title',
        'description',
        'status',
        'tags',
        'links',
        'image',
    ];

    protected $casts = [
        'links' => 'array',
        'tags' => 'array',
        'status' => TaskStatus::class,
    ];

    protected $attributes = [
        'status' => TaskStatus::PENDING->value,
    ];

    public static function statusCounts(User $user): Collection
    {
        $counts = $user->tasks()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(TaskStatus::cases())
            ->mapWithKeys(fn ($status) => [
                $status->value => $counts->get($status->value, 0),
            ])
            ->put('all', $user->tasks()->count());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class, 'idea_id');
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
}
