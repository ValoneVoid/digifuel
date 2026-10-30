<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemBackground extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function getActiveImageUrl(): string
    {
        try {
            if (Schema::hasTable('system_backgrounds')) {
                $active = static::where('is_active', true)->latest('updated_at')->first();
                if ($active && $active->image_path) {
                    return asset($active->image_path);
                }
            }
        } catch (\Throwable $e) {
            // Fallback if database/table not ready
        }

        return asset('images/mining_bg.jpg');
    }
}

