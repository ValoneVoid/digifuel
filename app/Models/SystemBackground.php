<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemBackground extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'overlay_opacity' => 'float',
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

    public static function getActiveOverlayOpacity(): float
    {
        try {
            if (Schema::hasTable('system_backgrounds') && Schema::hasColumn('system_backgrounds', 'overlay_opacity')) {
                $active = static::where('is_active', true)->latest('updated_at')->first();
                if ($active && !is_null($active->overlay_opacity)) {
                    return max(0.0, min(0.95, (float) $active->overlay_opacity));
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return 0.65; // Default darker background (65% darkness)
    }

    public static function getActiveBackground(): array
    {
        $imageUrl = static::getActiveImageUrl();
        $opacity = static::getActiveOverlayOpacity();

        return [
            'image_url' => $imageUrl,
            'overlay_opacity' => $opacity,
            'opacity_percent' => (int) round($opacity * 100),
        ];
    }
}

