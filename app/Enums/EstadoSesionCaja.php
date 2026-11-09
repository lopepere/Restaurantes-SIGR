<?php

namespace App\Enums;

enum EstadoSesionCaja: string
{
    case ABIERTA = 'abierta';
    case CERRADA = 'cerrada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ABIERTA => 'Abierta',
            self::CERRADA => 'Cerrada',
        };
    }
}
