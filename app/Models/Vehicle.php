<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'tank_capacity' => 'decimal:2',
    ];

    public function defaultArea()
    {
        return $this->belongsTo(Area::class, 'default_area_id');
    }

    public function fuelStandards()
    {
        return $this->hasMany(FuelStandard::class);
    }

    public function activeFuelStandard()
    {
        return $this->hasOne(FuelStandard::class)->where('is_active', true)->latest();
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }

    public function fuelEvaluations()
    {
        return $this->hasMany(FuelEvaluation::class);
    }
}
