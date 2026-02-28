<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItemMaterial extends Model
{
    protected $fillable = [
        'quotation_item_id',
        'assortment_item_id',
        'quantity',
        'unit',
        'unit_price',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function assortmentItem()
    {
        return $this->belongsTo(Assortment::class, 'assortment_item_id');
    }
}
