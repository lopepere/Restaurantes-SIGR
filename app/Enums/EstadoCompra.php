<?php

namespace App\Enums;

enum EstadoCompra: string
{
    case REGISTRADA = 'registrada';
    case ANULADA = 'anulada';
    case DEVUELTA = 'devuelta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::REGISTRADA => 'Registrada',
            self::ANULADA => 'Anulada',
            self::DEVUELTA => 'Devuelta',
        };
    }
}
