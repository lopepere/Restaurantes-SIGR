<?php

namespace App\Enums;

enum ModoInventario: string
{
    case AUTONOMO = 'autonomo';
    case DEPENDIENTE = 'dependiente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::AUTONOMO => 'Autónomo (registro manual por lote o producto)',
            self::DEPENDIENTE => 'Dependiente (alimentado por compras con factor de conversión)',
        };
    }
}
