<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price_per_liter' => 'decimal:2',
        'effective_start' => 'date',
        'effective_end' => 'date',
        'is_active' => 'boolean',
    ];

    public static function getCurrentPrice(string $date = null)
    {
        $targetDate = $date ?: now()->toDateString();
        return static::where('is_active', true)
            ->where('effective_start', '<=', $targetDate)
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('effective_end')
                    ->orWhere('effective_end', '>=', $targetDate);
            })
            ->latest('effective_start')
            ->first();
    }
}
