<?php

namespace App\Enums;

enum WorkstationStatus: string
{
    case IDLE = 'IDLE';
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case MAINTENANCE = 'MAINTENANCE';

    public function label(): string
    {
        return match ($this) {
            self::IDLE => 'Wolne',
            self::ACTIVE => 'Pracuje',
            self::PAUSED => 'Wstrzymane',
            self::MAINTENANCE => 'Konserwacja',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IDLE => 'green',
            self::ACTIVE => 'blue',
            self::PAUSED => 'orange',
            self::MAINTENANCE => 'red',
        };
    }
}
