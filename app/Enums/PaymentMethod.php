<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case TRANSFER = 'TRANSFER';
    case QR = 'QR';
    case CARD = 'CARD';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::TRANSFER => 'Transferencia',
            self::QR => 'QR',
            self::CARD => 'Tarjeta',
            self::OTHER => 'Otro',
        };
    }
}
