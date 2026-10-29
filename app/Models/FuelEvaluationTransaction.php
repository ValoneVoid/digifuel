<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelEvaluationTransaction extends Model
{
    protected $guarded = [];

    public function fuelEvaluation()
    {
        return $this->belongsTo(FuelEvaluation::class);
    }

    public function fuelTransaction()
    {
        return $this->belongsTo(FuelTransaction::class);
    }
}

