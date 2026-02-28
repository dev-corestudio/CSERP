<?php
namespace App\Enums;

enum AssortmentType: string
{
    case MATERIAL = 'MATERIAL';
    case SERVICE = 'SERVICE';

    public function label(): string
    {
        return match ($this) {
            self::MATERIAL => 'Materiał',
            self::SERVICE => 'Usługa',
        };
    }
}
