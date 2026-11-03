<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitVendor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'unit_vendor_id');
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class, 'unit_vendor_id');
    }
}

