<?php

namespace App\Enums;

enum ProjectOverallStatus: string
{
    case DRAFT = 'DRAFT';
    case QUOTATION = 'QUOTATION';
    case PROTOTYPE = 'PROTOTYPE';
    case PRODUCTION = 'PRODUCTION';
    case DELIVERY = 'DELIVERY';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Szkic',
            self::QUOTATION => 'Wycena',
            self::PROTOTYPE => 'Prototyp',
            self::PRODUCTION => 'Produkcja',
            self::DELIVERY => 'Dostawa',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'grey',
            self::QUOTATION => 'blue',
            self::PROTOTYPE => 'purple',
            self::PRODUCTION => 'orange',
            self::DELIVERY => 'cyan',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    // DODAJ IKONY
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'mdi-file-document-outline',
            self::QUOTATION => 'mdi-calculator',
            self::PROTOTYPE => 'mdi-test-tube',
            self::PRODUCTION => 'mdi-cog',
            self::DELIVERY => 'mdi-truck-delivery',
            self::COMPLETED => 'mdi-check-circle',
            self::CANCELLED => 'mdi-cancel',
        };
    }
}
