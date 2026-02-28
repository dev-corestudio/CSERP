<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = [
        'variant_id',
        'quantity',
        'total_estimated_cost',
        'total_actual_cost',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_estimated_cost' => 'decimal:2',
        'total_actual_cost' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Wariant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * Usługi produkcyjne (zadania)
     */
    public function services(): HasMany
    {
        return $this->hasMany(ProductionService::class);
    }
}
