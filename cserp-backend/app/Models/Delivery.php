<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\DeliveryStatus;

class Delivery extends Model
{
    protected $fillable = [
        'variant_id',
        'delivery_number',
        'delivery_date',
        'tracking_number',
        'courier',
        'status',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'status' => DeliveryStatus::class,
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
