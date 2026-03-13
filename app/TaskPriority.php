<?php

namespace App;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('task.priority_low'),
            self::MEDIUM => __('task.priority_medium'),
            self::HIGH => __('task.priority_high'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $priority) => $priority->value, self::cases());
    }
}

