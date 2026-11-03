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

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function getCurrentPrice(?string $date = null, ?int $vendorId = null)
    {
        $targetDate = $date ?: now()->toDateString();
        $query = static::where('is_active', true)
            ->whereDate('effective_start', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_end')
                    ->orWhereDate('effective_end', '>=', $targetDate);
            });

        if ($vendorId !== null) {
            $vendorPrice = (clone $query)->where('vendor_id', $vendorId)
                ->orderBy('effective_start', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($vendorPrice) {
                return $vendorPrice;
            }
        }

        return $query->orderBy('effective_start', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }
}
