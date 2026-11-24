<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelEvaluation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meter_start' => 'decimal:2',
        'meter_end' => 'decimal:2',
        'total_distance_or_hours' => 'decimal:2',
        'total_actual_liters' => 'decimal:2',
        'total_standard_liters' => 'decimal:2',
        'actual_ratio' => 'decimal:2',
        'standard_used' => 'decimal:2',
        'standard_max_used' => 'decimal:2',
        'tolerance_used' => 'decimal:2',
        'price_used' => 'decimal:2',
        'total_actual_cost' => 'decimal:2',
        'variance_liters' => 'decimal:2',
        'waste_liters' => 'decimal:2',
        'estimated_cost_loss' => 'decimal:2',
        'cumulative_meter_start' => 'decimal:2',
        'cumulative_meter_end' => 'decimal:2',
        'cumulative_distance' => 'decimal:2',
        'cumulative_liters' => 'decimal:2',
        'cumulative_ratio' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function calculator()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function evaluationTransactions()
    {
        return $this->hasMany(FuelEvaluationTransaction::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(FuelTransaction::class, 'fuel_evaluation_transactions');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->evaluation_status) {
            'normal' => 'Wajar',
            'needs_review' => 'Tidak Wajar',
            'incomplete' => 'Incomplete',
            'invalid_data' => 'Invalid Data',
            default => ucfirst((string) $this->evaluation_status),
        };
    }

    public function getCumulativeStatusLabelAttribute(): string
    {
        return match ($this->cumulative_status) {
            'normal' => 'Wajar',
            'needs_review' => 'Tidak Wajar',
            'incomplete' => 'Incomplete',
            'invalid_data' => 'Invalid Data',
            default => '-',
        };
    }

    public function getStandardRangeLabelAttribute(): string
    {
        $min = (float) $this->standard_used;
        $max = $this->standard_max_used !== null ? (float) $this->standard_max_used : null;
        if ($max !== null && $max > $min) {
            return "{$min} - {$max}";
        }
        return (string) $min;
    }
}

