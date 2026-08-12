<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'NEW';
    case PREPARING = 'PREPARING';
    case READY = 'READY';
    case DELIVERING = 'DELIVERING';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    /**
     * Get the display label for each status case.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nuevo',
            self::PREPARING => 'Preparando',
            self::READY => 'Listo',
            self::DELIVERING => 'En reparto',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Get a map of value => label for all statuses.
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
