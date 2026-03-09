<?php

namespace App;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('task.status_pending'),
            self::IN_PROGRESS => __('task.status_in_progress'),
            self::COMPLETED => __('task.status_completed'),
        };
    }

    public static function values()
    {
        return array_map(fn($status) => $status->value, static::cases());
    }
};
