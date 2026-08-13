<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
