<?php

namespace App\Enums;

enum EstadoCompra: string
{
    case REGISTRADA = 'registrada';
    case ANULADA = 'anulada';
    case DEVUELTA = 'devuelta';
    case DEVUELTA_PARCIAL = 'devuelta_parcial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::REGISTRADA => 'Registrada',
            self::ANULADA => 'Anulada',
            self::DEVUELTA => 'Devuelta',
            self::DEVUELTA_PARCIAL => 'Devuelta Parcial',
        };
    }
}
