<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Assortment;
use App\Enums\WorkstationType;
use App\Enums\WorkstationStatus;

class Workstation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'current_task_id',
        'location',
    ];

    protected $casts = [
        'type' => WorkstationType::class,
        'status' => WorkstationStatus::class,
    ];

    public function operators()
    {
        return $this->belongsToMany(User::class, 'workstation_operators')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function currentTask()
    {
        return $this->belongsTo(ProductionService::class, 'current_task_id');
    }



    public function allowedServices()
    {
        return $this->belongsToMany(Assortment::class, 'assortment_workstation')
            ->where('type', \App\Enums\AssortmentType::SERVICE)
            ->withTimestamps();
    }


}
