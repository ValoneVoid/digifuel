<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }
}
