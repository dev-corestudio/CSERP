<?php

namespace App\Enums;

/**
 * Priorytet zamówienia
 */
enum OrderPriority: string
{
    case LOW = 'LOW';
    case NORMAL = 'NORMAL';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';

    /**
     * Etykieta do wyświetlania
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Niski',
            self::NORMAL => 'Normalny',
            self::HIGH => 'Wysoki',
            self::URGENT => 'Pilny',
        };
    }

    /**
     * Kolor dla UI (Tailwind)
     */
    public function color(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::NORMAL => 'blue',
            self::HIGH => 'orange',
            self::URGENT => 'red',
        };
    }
}
