<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fuelRequest()
    {
        return $this->belongsTo(FuelRequest::class);
    }

    public static function notify(?int $userId, ?int $fuelRequestId, string $title, string $message, string $type = 'info'): ?self
    {
        if (!$userId) {
            return null;
        }
        return static::create([
            'user_id' => $userId,
            'fuel_request_id' => $fuelRequestId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }
}

