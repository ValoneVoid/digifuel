<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function approvers()
    {
        return $this->hasMany(AreaApprover::class);
    }

    public function activeApprovers()
    {
        return $this->hasMany(AreaApprover::class)->where('is_active', true);
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }
}
