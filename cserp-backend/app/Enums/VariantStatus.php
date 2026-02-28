<?php

namespace App\Enums;

enum VariantStatus: string
{
    case DRAFT = 'DRAFT';
    case QUOTATION = 'QUOTATION';
    case PRODUCTION = 'PRODUCTION';
    case DELIVERY = 'DELIVERY';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Szkic',
            self::QUOTATION => 'Wycena',
            self::PRODUCTION => 'Produkcja',
            self::DELIVERY => 'Dostawa',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'grey-lighten-1',
            self::QUOTATION => 'blue',
            self::PRODUCTION => 'orange',
            self::DELIVERY => 'cyan',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red-darken-4',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'mdi-pencil-outline',
            self::QUOTATION => 'mdi-calculator',
            self::PRODUCTION => 'mdi-cog',
            self::DELIVERY => 'mdi-truck-delivery',
            self::COMPLETED => 'mdi-check-circle',
            self::CANCELLED => 'mdi-cancel',
        };
    }
}
