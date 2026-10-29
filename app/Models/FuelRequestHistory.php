<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelRequestHistory extends Model
{
    protected $table = 'fuel_request_history';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function fuelRequest()
    {
        return $this->belongsTo(FuelRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?int $fuelRequestId, ?int $userId, string $action, ?string $fromStatus = null, ?string $toStatus = null, ?string $note = null): self
    {
        return static::create([
            'fuel_request_id' => $fuelRequestId,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}

