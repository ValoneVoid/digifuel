<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }

    public function fuelPrices()
    {
        return $this->hasMany(FuelPrice::class);
    }

    public function activeFuelPrice()
    {
        return $this->hasOne(FuelPrice::class)->where('is_active', true)->latest('effective_start');
    }
}
