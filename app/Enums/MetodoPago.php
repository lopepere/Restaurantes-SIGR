<?php

namespace App\Enums;

enum MetodoPago: string
{
    case EFECTIVO = 'efectivo';
    case TRANSFERENCIA = 'transferencia';
    case TARJETA_DEBITO = 'tarjeta_debito';
    case TARJETA_CREDITO = 'tarjeta_credito';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EFECTIVO => 'Efectivo',
            self::TRANSFERENCIA => 'Transferencia',
            self::TARJETA_DEBITO => 'Tarjeta débito',
            self::TARJETA_CREDITO => 'Tarjeta crédito',
        };
    }
}
