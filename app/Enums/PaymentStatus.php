<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PARTIAL = 'PARTIAL';
    case PAID = 'PAID';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PARTIAL => 'Parcial',
            self::PAID => 'Pagado',
        };
    }
}
