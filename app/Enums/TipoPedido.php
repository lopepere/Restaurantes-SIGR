<?php

namespace App\Enums;

enum TipoPedido: string
{
    case EN_SITIO = 'en_sitio';
    case PARA_LLEVAR = 'para_llevar';
    case DOMICILIO = 'domicilio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EN_SITIO => 'En sitio',
            self::PARA_LLEVAR => 'Para llevar',
            self::DOMICILIO => 'A domicilio',
        };
    }
}
