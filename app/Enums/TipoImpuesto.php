<?php

namespace App\Enums;

enum TipoImpuesto: string
{
    case IVA = 'iva';
    case INC = 'inc';
    case OTRO = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::IVA => 'Impuesto sobre las Ventas (IVA)',
            self::INC => 'Impuesto Nacional al Consumo (INC)',
            self::OTRO => 'Otro tributo',
        };
    }
}
