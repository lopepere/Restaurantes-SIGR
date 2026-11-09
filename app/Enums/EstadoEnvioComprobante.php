<?php

namespace App\Enums;

enum EstadoEnvioComprobante: string
{
    case ENCOLADO = 'encolado';
    case ENVIADO = 'enviado';
    case FALLIDO = 'fallido';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ENCOLADO => 'En cola de procesamiento',
            self::ENVIADO => 'Enviado satisfactoriamente',
            self::FALLIDO => 'Fallido / Error de entrega',
        };
    }
}
