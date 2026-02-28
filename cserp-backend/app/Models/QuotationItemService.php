<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItemService extends Model
{
    protected $fillable = [
        'quotation_item_id',
        'assortment_item_id',
        'estimated_quantity',
        'estimated_time_hours',
        'unit',
        'unit_price',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'estimated_quantity' => 'decimal:2',
        'estimated_time_hours' => 'decimal:2',
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
