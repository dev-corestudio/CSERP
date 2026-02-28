<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\InvoiceStatus;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',
        'total_net',
        'total_gross',
        'issue_date',
        'payment_deadline',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'total_net' => 'decimal:2',
        'total_gross' => 'decimal:2',
        'issue_date' => 'date',
        'payment_deadline' => 'date',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
