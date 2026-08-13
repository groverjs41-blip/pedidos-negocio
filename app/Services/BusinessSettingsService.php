<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

class BusinessSettingsService
{
    public const CACHE_KEY = 'business_settings';

    /**
     * Get the central business settings instance (cached).
     */
    public function getSettings(): BusinessSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return BusinessSetting::firstOrCreate(
                ['id' => 1],
                [
                    'business_name' => 'Pedidos Negocio',
                    'currency_name' => 'Bolivianos',
                    'currency_code' => 'BOB',
                    'currency_symbol' => 'Bs',
                    'currency_symbol_position' => 'BEFORE',
                    'currency_decimals' => 2,
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'timezone' => 'America/La_Paz',
                    'notification_sound_enabled' => true,
                    'notification_volume' => 80,
                    'kitchen_sound_enabled' => true,
                    'delivery_sound_enabled' => true,
                ]
            );
        });
    }

    /**
     * Update business settings and invalidate cache.
     */
    public function updateSettings(array $data): BusinessSetting
    {
        $settings = $this->getSettings();
        $settings->update($data);
        $this->clearCache();

        return $this->getSettings();
    }

    /**
     * Clear the business settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
