<?php

namespace App\Enums;

enum AssortmentUnit: string
{
    case SZT = 'SZT';
    case MB = 'MB';
    case M2 = 'M2';
    case KG = 'KG';
    case H = 'H';
    case KPL = 'KPL';
    case ROL = 'ROL';
    case OP = 'OP';
    case L = 'L';

    public function label(): string
    {
        return match ($this) {
            self::H => 'h',
            self::KG => 'kg',
            self::KPL => 'kpl.',
            self::L => 'litr',
            self::M2 => 'm²',
            self::MB => 'mb.',
            self::OP => 'opak.',
            self::ROL => 'rolka',
            self::SZT => 'szt.',
        };
    }
}
