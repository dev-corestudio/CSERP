<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TRANSFER = 'TRANSFER';
    case CASH = 'CASH';
    case CARD = 'CARD';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::TRANSFER => 'Przelew bankowy',
            self::CASH => 'Gotówka',
            self::CARD => 'Karta płatnicza',
            self::OTHER => 'Inny',
        };
    }
}
