<?php

namespace App\Enums;

enum EventType: string
{
    case START = 'START';
    case PAUSE = 'PAUSE';
    case RESUME = 'RESUME';
    case STOP = 'STOP';

    public function label(): string
    {
        return match ($this) {
            self::START => 'Rozpoczęcie',
            self::PAUSE => 'Pauza',
            self::RESUME => 'Wznowienie',
            self::STOP => 'Zakończenie',
        };
    }
}
