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
            self::PENDING => 'In afwachting',
            self::IN_PROGRESS => 'Bezig',
            self::COMPLETED => 'Voltooid',
        };
    }

    public static function values()
    {
        return array_map(fn($status) => $status->value, static::cases());
    }
};
