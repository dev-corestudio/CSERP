<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'UNPAID';
    case PARTIAL = 'PARTIAL';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Nieopłacone',
            self::PARTIAL => 'Częściowo opłacone',
            self::PAID => 'Opłacone',
            self::OVERDUE => 'Po terminie',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNPAID => 'grey',
            self::PARTIAL => 'orange',
            self::PAID => 'green',
            self::OVERDUE => 'red',
        };
    }

    // DODAJ IKONY
    public function icon(): string
    {
        return match ($this) {
            self::UNPAID => 'mdi-clock-outline',
            self::PARTIAL => 'mdi-clock-alert',
            self::PAID => 'mdi-check-circle',
            self::OVERDUE => 'mdi-alert-circle',
        };
    }
}
