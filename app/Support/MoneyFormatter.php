<?php

namespace App\Support;

use App\Services\BusinessSettingsService;

class MoneyFormatter
{
    /**
     * Format a monetary amount according to central business settings without float imprecision.
     */
    public static function format(string|int|float|null $amount): string
    {
        if ($amount === null || $amount === '') {
            $amount = '0.00';
        }

        $rawAmount = (string) $amount;
        if (!is_numeric($rawAmount)) {
            $rawAmount = '0.00';
        }

        $service = app(BusinessSettingsService::class);
        $settings = $service->getSettings();

        $decimals = (int) ($settings->currency_decimals ?? 2);
        $decSep = $settings->decimal_separator ?? ',';
        $thousandsSep = $settings->thousands_separator ?? '.';
        $symbol = $settings->currency_symbol ?? 'Bs';
        $position = strtoupper($settings->currency_symbol_position ?? 'BEFORE');

        // Normalize decimal string to fixed decimal scale using bcadd
        $normalized = bcadd($rawAmount, '0', $decimals);

        $isNegative = str_starts_with($normalized, '-');
        if ($isNegative) {
            $normalized = substr($normalized, 1);
        }

        $parts = explode('.', $normalized);
        $integerPart = $parts[0];
        $fractionalPart = $parts[1] ?? '';

        // Add thousands separator to integer part using string regex
        $formattedInteger = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandsSep, $integerPart);

        $formattedValue = $formattedInteger;
        if ($decimals > 0 && $fractionalPart !== '') {
            $formattedValue .= $decSep . $fractionalPart;
        }

        if ($isNegative) {
            $formattedValue = '-' . $formattedValue;
        }

        if ($position === 'AFTER') {
            return $formattedValue . ' ' . $symbol;
        }

        return $symbol . ' ' . $formattedValue;
    }
}
