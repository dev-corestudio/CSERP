<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'assigned_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => CustomerType::class,
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    // Opiekun klienta (Handlowiec lub Project Manager)
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
