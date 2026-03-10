<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'user_id',
        'is_admin',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function supportMessage(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
