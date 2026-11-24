<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelStandard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'standard_value' => 'decimal:2',
        'standard_max' => 'decimal:2',
        'tolerance_percentage' => 'decimal:2',
        'effective_start' => 'date',
        'effective_end' => 'date',
        'is_active' => 'boolean',
    ];

    public function getRangeLabelAttribute(): string
    {
        $min = (float) $this->standard_value;
        $max = $this->standard_max !== null ? (float) $this->standard_max : null;

        if ($max !== null && $max > $min) {
            return "{$min} - {$max}";
        }

        return (string) $min;
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
