<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportMessage extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_FOR_USER = 'waiting_for_user';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'subject',
        'category',
        'message',
        'status',
        'resolved_at',
        'admin_resolved_at',
        'user_resolved_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'admin_resolved_at' => 'datetime',
            'user_resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportMessageReply::class)->orderBy('created_at');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_FOR_USER,
            self::STATUS_RESOLVED,
        ];
    }

    public function scopeIncoming($query)
    {
        return $query->where(function ($query): void {
            $query->whereNull('admin_resolved_at')
                ->orWhere(function ($query): void {
                    $query->whereNotNull('user_id')
                        ->whereNotNull('admin_resolved_at')
                        ->whereNull('user_resolved_at');
                });
        });
    }

    public function scopeFullyResolved($query)
    {
        return $query->whereNotNull('admin_resolved_at')
            ->where(function ($query): void {
                $query->whereNull('user_id')
                    ->orWhereNotNull('user_resolved_at');
            });
    }

    public function requiresUserResolutionConfirmation(): bool
    {
        return $this->user_id !== null
            && $this->admin_resolved_at !== null
            && $this->user_resolved_at === null;
    }

    public function isFullyResolved(): bool
    {
        return $this->admin_resolved_at !== null
            && ($this->user_id === null || $this->user_resolved_at !== null);
    }
}
