<?php

namespace App\Enums;

enum TipoEnvioComprobante: string
{
    case EMAIL_CLIENTE = 'email_cliente';
    case EMAIL_DESIGNADO = 'email_designado';
    case DESCARGA = 'descarga';
    case IMPRESION = 'impresion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EMAIL_CLIENTE => 'Envío al correo del cliente',
            self::EMAIL_DESIGNADO => 'Envío a correo(s) designado(s)',
            self::DESCARGA => 'Descarga de archivo PDF',
            self::IMPRESION => 'Impresión física (A4 / tirilla 80 mm)',
        };
    }
}
