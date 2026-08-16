<?php

namespace App\Enums;

enum ServiceMode: string
{
    case KITCHEN = 'KITCHEN';
    case DIRECT = 'DIRECT';

    /**
     * Get the display label for each service mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::KITCHEN => 'Enviar a cocina',
            self::DIRECT => 'Venta en puesto',
        };
    }

    /**
     * Get a map of value => label for all service modes.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }
        return $labels;
    }
}
