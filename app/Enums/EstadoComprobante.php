<?php

namespace App\Enums;

enum EstadoComprobante: string
{
    case EMITIDO = 'emitido';
    case ANULADO = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EMITIDO => 'Emitido',
            self::ANULADO => 'Anulado',
        };
    }
}
