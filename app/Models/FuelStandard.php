<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelStandard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'standard_value' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'effective_start' => 'date',
        'effective_end' => 'date',
        'is_active' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
