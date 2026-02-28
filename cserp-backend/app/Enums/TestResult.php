<?php

namespace App\Enums;

enum TestResult: string
{
    case PENDING = 'PENDING';
    case PASSED = 'PASSED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Oczekuje na test',
            self::PASSED => 'Zatwierdzony',
            self::FAILED => 'Odrzucony',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PASSED => 'green',
            self::FAILED => 'red',
        };
    }
}
