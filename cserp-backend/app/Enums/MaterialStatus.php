<?php

namespace App\Enums;

enum MaterialStatus: string
{
    case NOT_ORDERED = 'NOT_ORDERED';
    case ORDERED = 'ORDERED';
    case PARTIALLY_IN_STOCK = 'PARTIALLY_IN_STOCK';
    case IN_STOCK = 'IN_STOCK';

    public function label(): string
    {
        return match ($this) {
            self::NOT_ORDERED => 'Niezamówiony',
            self::ORDERED => 'Zamówiony',
            self::PARTIALLY_IN_STOCK => 'Częściowo na stanie',
            self::IN_STOCK => 'Na stanie',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_ORDERED => 'red',
            self::ORDERED => 'orange',
            self::PARTIALLY_IN_STOCK => 'blue',
            self::IN_STOCK => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NOT_ORDERED => 'mdi-cart-outline',
            self::ORDERED => 'mdi-truck-fast-outline',
            self::PARTIALLY_IN_STOCK => 'mdi-package-variant',
            self::IN_STOCK => 'mdi-package-variant-closed-check',
        };
    }
}
