<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\AssortmentHistoryAction;

class AssortmentHistory extends Model
{
    protected $table = 'assortment_history';

    protected $fillable = [
        'assortment_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'action' => AssortmentHistoryAction::class,
    ];

    public function assortment()
    {
        return $this->belongsTo(Assortment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pobierz opis zmiany
     */
    public function getChangeDescriptionAttribute()
    {
        if ($this->description) {
            return $this->description;
        }

        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;

                if ($oldValue != $newValue) {
                    $changes[] = $this->formatFieldChange($key, $oldValue, $newValue);
                }
            }
        }

        return !empty($changes) ? implode(', ', $changes) : 'Brak zmian';
    }

    /**
     * Formatuj zmianę pola
     */
    private function formatFieldChange($field, $oldValue, $newValue)
    {
        $fieldLabels = [
            'name' => 'Nazwa',
            'type' => 'Typ',
            'category' => 'Kategoria',
            'unit' => 'Jednostka',
            'default_price' => 'Cena',
            'description' => 'Opis',
            'is_active' => 'Status'
        ];

        $label = $fieldLabels[$field] ?? $field;

        if ($field === 'is_active') {
            $oldValue = $oldValue ? 'Aktywna' : 'Nieaktywna';
            $newValue = $newValue ? 'Aktywna' : 'Nieaktywna';
        } elseif ($field === 'default_price') {
            $oldValue = number_format($oldValue, 2, ',', ' ') . ' PLN';
            $newValue = number_format($newValue, 2, ',', ' ') . ' PLN';
        }

        return "{$label}: {$oldValue} → {$newValue}";
    }
}
