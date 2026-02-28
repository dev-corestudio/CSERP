<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'materials_cost',
        'services_cost',
        'subtotal',
    ];

    protected $casts = [
        'materials_cost' => 'decimal:2',
        'services_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function materials()
    {
        return $this->hasMany(QuotationItemMaterial::class);
    }

    public function services()
    {
        return $this->hasMany(QuotationItemService::class);
    }
}
