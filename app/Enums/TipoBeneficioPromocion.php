<?php

namespace App\Enums;

enum TipoBeneficioPromocion: string
{
    case PORCENTAJE = 'porcentaje';
    case VALOR_FIJO = 'valor_fijo';
    case PRECIO_ESPECIAL = 'precio_especial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PORCENTAJE => 'Porcentaje de descuento',
            self::VALOR_FIJO => 'Monto fijo de descuento',
            self::PRECIO_ESPECIAL => 'Precio especial promocional',
        };
    }
}
