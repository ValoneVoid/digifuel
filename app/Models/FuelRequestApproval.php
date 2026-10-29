<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelRequestApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function fuelRequest()
    {
        return $this->belongsTo(FuelRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
