<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\AssortmentType;
use App\Enums\AssortmentUnit;
use App\Enums\AssortmentHistoryAction;

class Assortment extends Model
{
    protected $table = 'assortment';

    protected $fillable = [
        'type',
        'name',
        'category',
        'unit',
        'default_price',
        'description',
        'is_active',
    ];


    protected $casts = [
        'type' => AssortmentType::class,
        'unit' => AssortmentUnit::class,
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Boot method dla automatycznego logowania zmian
    protected static function boot()
    {
        parent::boot();

        static::created(function ($assortment) {
            $assortment->logHistory(
                AssortmentHistoryAction::CREATED,
                null,
                $assortment->getAttributes(),
                'Pozycja utworzona'
            );
        });

        static::updated(function ($assortment) {
            $changes = $assortment->getChanges();
            if (!empty($changes)) {
                unset($changes['updated_at']);
                if (!empty($changes)) {
                    $assortment->logHistory(
                        AssortmentHistoryAction::UPDATED,
                        $assortment->getOriginal(),
                        $changes
                    );
                }
            }
        });

        static::deleted(function ($assortment) {
            $assortment->logHistory(
                AssortmentHistoryAction::DELETED,
                $assortment->getAttributes(),
                null,
                'Pozycja usunięta'
            );
        });
    }

    /**
     * Historia zmian
     */
    public function history()
    {
        return $this->hasMany(AssortmentHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Loguj zmianę
     */
    public function logHistory($action, $oldValues = null, $newValues = null, $description = null)
    {
        // Konwersja string na Enum jeśli potrzeba
        if (is_string($action)) {
            $action = AssortmentHistoryAction::from($action);
        }

        return AssortmentHistory::create([
            'assortment_id' => $this->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }

    public function scopeMaterials($query)
    {
        return $query->where('type', 'material');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


}
