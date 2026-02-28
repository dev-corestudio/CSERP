<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case ISSUED = 'ISSUED';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => 'Wystawiona',
            self::PAID => 'Opłacona',
            self::OVERDUE => 'Przeterminowana',
            self::CANCELLED => 'Anulowana',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ISSUED => 'blue',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
