<?php

namespace App\Enums;

enum WorkstationType: string
{
    case LASER = 'LASER';
    case CNC = 'CNC';
    case ASSEMBLY = 'ASSEMBLY';
    case PRINTING = 'PRINTING';
    case PAINTING = 'PAINTING';
    case PRODUCTION = 'PRODUCTION';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::LASER => 'Laser',
            self::CNC => 'CNC',
            self::ASSEMBLY => 'Montaż',
            self::PRINTING => 'Drukarnia',
            self::PAINTING => 'Malarnia',
            self::PRODUCTION => 'Produkcja',
            self::OTHER => 'Inne',
        };
    }
}
