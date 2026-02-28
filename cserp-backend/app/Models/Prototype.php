<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TestResult;
use App\Enums\MaterialStatus;
use App\Enums\ProductionStatus;

/**
 * Model Prototype (Prototyp)
 *
 * Każdy prototyp ma ODDZIELNE:
 * - Materiały (prototype_materials) — niezależne od materiałów wariantu/produkcji seryjnej
 * - Zadania RCP (prototype_services) — niezależne od production_services linii seryjnej
 *
 * Wariant może mieć wiele prototypów, ale tylko jeden zatwierdzony.
 */
class Prototype extends Model
{
    protected $fillable = [
        'variant_id',
        'version_number',
        'is_approved',
        'test_result',
        'feedback_notes',
        'sent_to_client_date',
        'client_response_date',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'sent_to_client_date' => 'date',
        'client_response_date' => 'date',
        'test_result' => TestResult::class,
    ];

    // =========================================================================
    // RELACJE
    // =========================================================================

    /**
     * Wariant, do którego należy prototyp
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * Materiały prototypu (ODDZIELNE od materiałów wariantu)
     * Prototyp ma swoją własną listę materiałów z info o zamówieniach/stanach
     */
    public function materials(): HasMany
    {
        return $this->hasMany(PrototypeMaterial::class);
    }

    /**
     * Zadania produkcyjne / RCP prototypu (ODDZIELNE od production_services)
     * Prototyp ma swoje własne zadania robocze
     */
    public function services(): HasMany
    {
        return $this->hasMany(PrototypeService::class);
    }

    // =========================================================================
    // MATERIAŁY - HELPERY
    // =========================================================================

    /**
     * Czy wszystkie materiały prototypu są na stanie
     */
    public function allMaterialsInStock(): bool
    {
        if ($this->materials->isEmpty()) {
            return true; // prototyp bez materiałów = gotowy
        }

        return $this->materials->every(fn($m) => $m->status === MaterialStatus::IN_STOCK);
    }

    /**
     * Łączny koszt materiałów prototypu
     */
    public function getTotalMaterialsCostAttribute(): float
    {
        return (float) $this->materials->sum('total_cost');
    }

    /**
     * Podsumowanie statusów materiałów prototypu
     */
    public function getMaterialsStatusSummaryAttribute(): array
    {
        $materials = $this->materials;

        return [
            'total' => $materials->count(),
            'not_ordered' => $materials->where('status', MaterialStatus::NOT_ORDERED)->count(),
            'ordered' => $materials->where('status', MaterialStatus::ORDERED)->count(),
            'in_stock' => $materials->where('status', MaterialStatus::IN_STOCK)->count(),
            'all_ready' => $this->allMaterialsInStock(),
        ];
    }

    // =========================================================================
    // RCP / SERVICES - HELPERY
    // =========================================================================

    /**
     * Czy wszystkie zadania RCP prototypu zostały zakończone
     */
    public function allServicesCompleted(): bool
    {
        if ($this->services->isEmpty()) {
            return true;
        }

        return $this->services->every(fn($s) => $s->status === ProductionStatus::COMPLETED);
    }

    /**
     * Łączny szacowany koszt usług prototypu
     */
    public function getTotalServicesCostAttribute(): float
    {
        return (float) $this->services->sum('estimated_cost');
    }

    /**
     * Łączny rzeczywisty koszt usług prototypu
     */
    public function getTotalActualServicesCostAttribute(): float
    {
        return (float) $this->services->sum('actual_cost');
    }

    /**
     * Łączny koszt prototypu (materiały + usługi)
     */
    public function getTotalCostAttribute(): float
    {
        return $this->total_materials_cost + $this->total_services_cost;
    }
}
