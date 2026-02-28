<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\MaterialStatus;

class VariantMaterial extends Model
{
    protected $fillable = [
        'variant_id',
        'assortment_id',
        'quantity',
        'unit',
        'unit_price',
        'total_cost',
        'status',
        'expected_delivery_date',
        'ordered_at',
        'received_at',
        'quantity_in_stock',
        'quantity_ordered',
        'supplier',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity_in_stock' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'ordered_at' => 'date',
        'received_at' => 'date',
        'status' => MaterialStatus::class,
    ];

    /**
     * Wariant, do którego przypisany jest materiał
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * Pozycja asortymentu (materiał z katalogu)
     */
    public function assortment(): BelongsTo
    {
        return $this->belongsTo(Assortment::class, 'assortment_id');
    }

    /**
     * Czy materiał jest w pełni dostępny
     */
    public function isFullyAvailable(): bool
    {
        return $this->status === MaterialStatus::IN_STOCK;
    }

    /**
     * Czy materiał wymaga zamówienia
     */
    public function needsOrdering(): bool
    {
        return $this->status === MaterialStatus::NOT_ORDERED;
    }

    /**
     * Oblicz koszt całkowity na podstawie ilości i ceny jednostkowej
     */
    public function recalculateTotalCost(): void
    {
        $this->total_cost = $this->quantity * $this->unit_price;
    }
}
