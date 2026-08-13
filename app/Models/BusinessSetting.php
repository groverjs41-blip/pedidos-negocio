<?php

namespace App\Models;

use App\Services\BusinessSettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'currency_name',
        'currency_code',
        'currency_symbol',
        'currency_symbol_position',
        'currency_decimals',
        'decimal_separator',
        'thousands_separator',
        'timezone',
        'notification_sound_enabled',
        'notification_volume',
        'kitchen_sound_enabled',
        'delivery_sound_enabled',
    ];

    protected $casts = [
        'currency_decimals' => 'integer',
        'notification_sound_enabled' => 'boolean',
        'notification_volume' => 'integer',
        'kitchen_sound_enabled' => 'boolean',
        'delivery_sound_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget(BusinessSettingsService::CACHE_KEY);
        });
        static::deleted(function () {
            Cache::forget(BusinessSettingsService::CACHE_KEY);
        });
    }
}
