<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\CustomerType;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'nip',
        'email',
        'phone',
        'address',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => CustomerType::class,
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
