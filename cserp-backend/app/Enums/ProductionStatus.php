<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case PLANNED = 'PLANNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case PAUSED = 'PAUSED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planowane',
            self::IN_PROGRESS => 'W toku',
            self::PAUSED => 'Wstrzymane',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }
}
