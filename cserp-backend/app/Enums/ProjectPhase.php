<?php

namespace App\Enums;

enum ProjectPhase: string
{
    case QUOTATION = 'quotation';
    case PROTOTYPE = 'prototype';
    case PRODUCTION = 'production';
    case DELIVERY = 'delivery';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::QUOTATION => 'Wycena',
            self::PROTOTYPE => 'Prototyp',
            self::PRODUCTION => 'Produkcja',
            self::DELIVERY => 'Dostawa',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }
}
