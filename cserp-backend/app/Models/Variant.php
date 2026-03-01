<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\VariantType;
use App\Enums\VariantStatus;
use App\Enums\MaterialStatus;

class Variant extends Model
{
    protected $table = 'variants';

    protected $fillable = [
        'project_id',
        'parent_variant_id',
        'is_group',
        'variant_number',
        'name',
        'description',
        'quantity',
        'type',
        'status',
        'is_approved',
        'feedback_notes',
        'approved_prototype_id',
        'tkw_z_wyceny',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'quantity' => 'integer',
        'type' => VariantType::class,
        'status' => VariantStatus::class,
        'is_approved' => 'boolean',
        'tkw_z_wyceny' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacje

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentVariant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'parent_variant_id');
    }

    public function childVariants(): HasMany
    {
        return $this->hasMany(Variant::class, 'parent_variant_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(VariantMaterial::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function approvedQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->where('is_approved', true);
    }

    public function prototypes(): HasMany
    {
        return $this->hasMany(Prototype::class);
    }

    public function productionOrder(): HasOne
    {
        return $this->hasOne(ProductionOrder::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    // Pomocnicze

    public function isGroup(): bool
    {
        return $this->is_group === true;
    }

    public function isPrototype(): bool
    {
        return $this->type === VariantType::PROTOTYPE;
    }

    public function isSerial(): bool
    {
        return $this->type === VariantType::SERIAL;
    }

    public function isChild(): bool
    {
        return $this->parent_variant_id !== null;
    }

    /**
     * Wszyscy potomkowie rekurencyjnie — uzywane przy force delete.
     */
    public function allDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        foreach ($this->childVariants as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->allDescendants());
        }
        return $descendants;
    }

    public function getMaterialsStatusSummaryAttribute(): array
    {
        $materials = $this->materials;
        return [
            'total' => $materials->count(),
            'not_ordered' => $materials->where('status', MaterialStatus::NOT_ORDERED)->count(),
            'ordered' => $materials->where('status', MaterialStatus::ORDERED)->count(),
            'partially_in_stock' => $materials->where('status', MaterialStatus::PARTIALLY_IN_STOCK)->count(),
            'in_stock' => $materials->where('status', MaterialStatus::IN_STOCK)->count(),
            'all_ready' => $this->allMaterialsInStock(),
        ];
    }

    public function allMaterialsInStock(): bool
    {
        if ($this->materials->isEmpty())
            return false;
        return $this->materials->every(fn($m) => $m->status === MaterialStatus::IN_STOCK);
    }

    public function getTotalMaterialsCostAttribute(): float
    {
        return (float) $this->materials->sum('total_cost');
    }
}
