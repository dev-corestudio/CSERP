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

}