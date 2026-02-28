<?php

namespace App\Enums;

enum CustomerType: string
{
    case B2B = 'B2B';
    case B2C = 'B2C';

    public function label(): string
    {
        return match ($this) {
            self::B2B => 'Firma (B2B)',
            self::B2C => 'Klient Indywidualny (B2C)',
        };
    }
}
