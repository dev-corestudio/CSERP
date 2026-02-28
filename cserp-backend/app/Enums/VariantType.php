<?php

namespace App\Enums;

enum VariantType: string
{
    case PROTOTYPE = 'PROTOTYPE';
    case SERIAL = 'SERIAL';

    public function label(): string
    {
        return match ($this) {
            self::PROTOTYPE => 'Prototyp',
            self::SERIAL => 'Produkcja Seryjna',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PROTOTYPE => 'purple',
            self::SERIAL => 'blue',
        };
    }
}
