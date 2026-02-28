<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use App\Enums\OrderOverallStatus;
use App\Enums\PaymentStatus;
use App\Enums\OrderPriority;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_number',
        'series',
        'description', // Zmiana z brief
        'planned_delivery_date', // Nowe pole
        'overall_status',
        'payment_status',
        'priority',
    ];

    protected $casts = [
        'overall_status' => OrderOverallStatus::class,
        'payment_status' => PaymentStatus::class,
        'priority' => OrderPriority::class,
        'planned_delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Dodajemy append, aby frontend łatwo dostał pełny numer
    protected $appends = ['full_order_number'];

    /**
     * Logika generowania serii dla danego numeru zamówienia.
     */
    public static function generateSeries(string $orderNumber): string
    {
        // Pobierz najwyższą serię dla tego numeru
        $lastSeries = self::where('order_number', $orderNumber)
            ->max('series');

        if (!$lastSeries) {
            return '0001';
        }

        $nextSeries = intval($lastSeries) + 1;
        return str_pad((string) $nextSeries, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Akcesor dla pełnego numeru wyświetlanego na froncie: Z/XXXX/YYYY
     */
    public function getFullOrderNumberAttribute(): string
    {
        return "Z/{$this->order_number}/{$this->series}";
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
        return $this->hasMany(OrderImage::class)->orderBy('sort_order');
    }

    /**
     * Zlecenia produkcyjne (przez warianty)
     */
    public function productionOrders(): HasManyThrough
    {
        return $this->hasManyThrough(ProductionOrder::class, Variant::class);
    }

    /**
     * Sprawdź czy zamówienie ma zatwierdzoną wycenę
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
