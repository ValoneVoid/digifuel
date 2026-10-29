<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requested_liters' => 'decimal:2',
        'actual_liters' => 'decimal:2',
        'variance_liters' => 'decimal:2',
        'meter_at_fueling' => 'decimal:2',
        'fueled_at' => 'datetime',
    ];

    public function fuelRequest()
    {
        return $this->belongsTo(FuelRequest::class);
    }

    public function fuelman()
    {
        return $this->belongsTo(User::class, 'fuelman_id');
    }

    public function evaluationTransactions()
    {
        return $this->hasMany(FuelEvaluationTransaction::class);
    }
}
