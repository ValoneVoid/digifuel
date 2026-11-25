<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportPoint extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }
}
