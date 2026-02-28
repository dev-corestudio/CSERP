<?php

namespace App\Enums;

enum AssortmentHistoryAction: string
{
    case CREATED = 'CREATED';
    case UPDATED = 'UPDATED';
    case DELETED = 'DELETED';
    case ACTIVATED = 'ACTIVATED';
    case DEACTIVATED = 'DEACTIVATED';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Utworzono',
            self::UPDATED => 'Zaktualizowano',
            self::DELETED => 'Usunięto',
            self::ACTIVATED => 'Aktywowano',
            self::DEACTIVATED => 'Dezaktywowano',
        };
    }
}
