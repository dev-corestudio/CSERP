<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Zaplanowana',
            self::IN_TRANSIT => 'W transporcie',
            self::DELIVERED => 'Dostarczona',
            self::CANCELLED => 'Anulowana',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'blue',
            self::IN_TRANSIT => 'orange',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
