<?php

namespace App\Support;

use App\Services\BusinessSettingsService;

class MoneyFormatter
{
    /**
     * Format a monetary amount according to central business settings.
     */
    public static function format(string|int|float|null $amount): string
    {
        if ($amount === null || $amount === '') {
            $amount = '0.00';
        }

        // Clean amount into standard string format (e.g. 1250.50)
        $rawAmount = (string) $amount;
        if (!is_numeric($rawAmount)) {
            $rawAmount = '0';
        }

        $service = app(BusinessSettingsService::class);
        $settings = $service->getSettings();

        $decimals = (int) $settings->currency_decimals;
        $decSep = $settings->decimal_separator ?? ',';
        $thousandsSep = $settings->thousands_separator ?? '.';
        $symbol = $settings->currency_symbol ?? 'Bs';
        $position = strtoupper($settings->currency_symbol_position ?? 'BEFORE');

        // Split integer and fractional parts as strings to avoid floating point imprecision
        $parts = explode('.', $rawAmount);
        $integerPart = $parts[0];
        $fractionalPart = $parts[1] ?? '0';

        // Ensure negative sign handled correctly
        $isNegative = false;
        if (str_starts_with($integerPart, '-')) {
            $isNegative = true;
            $integerPart = substr($integerPart, 1);
        }

        // Round fractional part safely to required decimals
        if ($decimals > 0) {
            $fractionalPart = str_pad($fractionalPart, $decimals, '0');
            // If fractional part exceeds decimals, round properly using string arithmetic or number_format
            if (strlen($fractionalPart) > $decimals) {
                // Round fractional part safely
                $formattedNum = number_format((float)$rawAmount, $decimals, '.', '');
                $parts = explode('.', $formattedNum);
                $integerPart = ltrim($parts[0], '-');
                $fractionalPart = $parts[1] ?? str_repeat('0', $decimals);
            }
        } else {
            $fractionalPart = '';
        }

        // Format integer part with thousands separator
        $formattedInteger = number_format((float)$integerPart, 0, '', $thousandsSep);

        // Reconstruct formatted string
        $formattedValue = $formattedInteger;
        if ($decimals > 0 && $fractionalPart !== '') {
            $formattedValue .= $decSep . substr($fractionalPart, 0, $decimals);
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
