<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ProductionStatus;

class PrototypeService extends Model
{
    protected $fillable = [
        'prototype_id',
        'step_number',
        'service_name',
        'workstation_id',
        'assigned_to_user_id',
        'estimated_quantity',
        'estimated_time_hours',
        'unit_price',
        'estimated_cost',
        'actual_quantity',
        'actual_time_hours',
        'actual_cost',
        'total_pause_duration_seconds',
        'status',
        'actual_start_date',
        'actual_end_date',
        'worker_notes',
    ];

    protected $casts = [
        'estimated_quantity' => 'decimal:2',
        'estimated_time_hours' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'actual_time_hours' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'status' => ProductionStatus::class,
    ];

    /**
     * Prototyp, do którego należy zadanie
     */
    public function prototype(): BelongsTo
    {
        return $this->belongsTo(Prototype::class);
    }

    /**
     * Stanowisko robocze
     */
    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }

    /**
     * Przypisany pracownik
     */
    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Czy zadanie jest aktywne
     */
    public function isActive(): bool
    {
        return $this->status === ProductionStatus::IN_PROGRESS;
    }

    /**
     * Czy zadanie jest wstrzymane
     */
    public function isPaused(): bool
    {
        return $this->status === ProductionStatus::PAUSED;
    }

    /**
     * Oblicz szacowany koszt
     */
    public function recalculateEstimatedCost(): void
    {
        $this->estimated_cost = $this->estimated_quantity * $this->unit_price;
    }
}
