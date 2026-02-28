<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case PROJECT_MANAGER = 'PROJECT_MANAGER';
    case TRADER = 'TRADER'; // Handlowiec
    case PRODUCTION_EMPLOYEE = 'PRODUCTION_EMPLOYEE'; // Pracownik Produkcji (dawniej WORKER)
    case ADMINISTRATIVE_EMPLOYEE = 'ADMINISTRATIVE_EMPLOYEE'; // Pracownik Biurowy
    case LOGISTICS_SPECIALIST = 'LOGISTICS_SPECIALIST'; // Logistyk

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::PROJECT_MANAGER => 'Project Manager',
            self::TRADER => 'Handlowiec',
            self::PRODUCTION_EMPLOYEE => 'Pracownik Produkcji',
            self::ADMINISTRATIVE_EMPLOYEE => 'Pracownik Biurowy',
            self::LOGISTICS_SPECIALIST => 'Specjalista ds. Logistyki',
        };
    }
}
