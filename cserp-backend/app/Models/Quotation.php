<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'variant_id',
        'version_number',
        'total_materials_cost',
        'total_services_cost',
        'total_net',
        'total_gross',
        'margin_percent',
        'is_approved',
        'approved_at',
        'approved_by_user_id',
        'notes',
    ];

    protected $casts = [
        'total_materials_cost' => 'decimal:2',
        'total_services_cost' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_gross' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
