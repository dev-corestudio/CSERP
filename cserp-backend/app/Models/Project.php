<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use App\Enums\ProjectOverallStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectPriority;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'customer_id',
        'project_number',
        'series',
        'description',
        'planned_delivery_date',
        'overall_status',
        'payment_status',
        'priority',
    ];

    protected $casts = [
        'overall_status' => ProjectOverallStatus::class,
        'payment_status' => PaymentStatus::class,
        'priority' => ProjectPriority::class,
        'planned_delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['full_project_number'];

    /**
     * Logika generowania serii dla danego numeru projektu.
     */
    public static function generateSeries(string $projectNumber): string
    {
        // Pobierz najwyższą serię dla tego numeru
        $lastSeries = self::where('project_number', $projectNumber)
            ->max('series');

        if (!$lastSeries) {
            return '0001';
        }

        $nextSeries = intval($lastSeries) + 1;
        return str_pad((string) $nextSeries, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Akcesor dla pełnego numeru wyświetlanego na froncie: P/XXXX/YYYY
     */
    public function getFullProjectNumberAttribute(): string
    {
        return "P/{$this->project_number}/{$this->series}";
    }

    /**
     * Klient
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Warianty
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * Wyceny (przez warianty)
     */
    public function quotations(): HasManyThrough
    {
        return $this->hasManyThrough(Quotation::class, Variant::class);
    }

    /**
     * Faktury
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Zdjęcia
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    /**
     * Zlecenia produkcyjne (przez warianty)
     */
    public function productionOrders(): HasManyThrough
    {
        return $this->hasManyThrough(ProductionOrder::class, Variant::class);
    }

    /**
     * Sprawdź czy projekt ma zatwierdzoną wycenę
     */
    public function hasApprovedQuotation(): bool
    {
        return $this->quotations()->where('is_approved', true)->exists();
    }

    /**
     * Pobierz sumę wartości wszystkich zatwierdzonych wycen
     */
    public function getApprovedQuotationsTotalAttribute(): float
    {
        return $this->quotations()
            ->where('is_approved', true)
            ->sum('total_gross');
    }
}
