<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProductionStatus;

class ProductionService extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
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
        'time_variance_hours',
        'cost_variance',
        'variance_percent',
        'total_pause_duration_seconds',
        'status',
        'planned_start_date',
        'planned_end_date',
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
        'time_variance_hours' => 'decimal:2',
        'cost_variance' => 'decimal:2',
        'variance_percent' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'status' => ProductionStatus::class,
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function assignedWorker()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function timeLogs()
    {
        return $this->hasMany(ServiceTimeLog::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', ProductionStatus::PLANNED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', ProductionStatus::IN_PROGRESS);
    }

    public function isActive(): bool
    {
        return $this->status === ProductionStatus::IN_PROGRESS;
    }

    public function isPaused(): bool
    {
        return $this->status === ProductionStatus::PAUSED;
    }
}
