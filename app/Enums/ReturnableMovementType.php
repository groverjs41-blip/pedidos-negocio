<?php

namespace App\Enums;

enum ReturnableMovementType: string
{
    case OUT = 'OUT';
    case RETURN = 'RETURN';

    public function label(): string
    {
        return match ($this) {
            self::OUT => 'Entregado al cliente',
            self::RETURN => 'Recuperado',
        };
    }
}
