<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EventType;

class ServiceTimeLog extends Model
{
    protected $fillable = [
        'production_service_id',
        'user_id',
        'event_type',
        'event_timestamp',
        'elapsed_seconds',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'elapsed_seconds' => 'integer',
        'event_type' => EventType::class,
    ];

    public function productionService()
    {
        return $this->belongsTo(ProductionService::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
