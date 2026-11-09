<?php

namespace App\Enums;

enum OrigenUsuario: string
{
    case REGISTRO = 'registro';
    case PEDIDO = 'pedido';
    case RESERVA = 'reserva';
    case PERSONAL = 'personal';

    public function etiqueta(): string
    {
        return match ($this) {
            self::REGISTRO => 'Registro voluntario (home)',
            self::PEDIDO => 'Primer pedido sin cuenta',
            self::RESERVA => 'Primera reserva sin cuenta',
            self::PERSONAL => 'Personal administrativo / operativo',
        };
    }
}
