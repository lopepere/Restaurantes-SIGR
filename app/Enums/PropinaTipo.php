<?php

namespace App\Enums;

enum PropinaTipo: string
{
    case NINGUNA = 'ninguna';
    case PORCENTAJE = 'porcentaje';
    case ABIERTA = 'abierta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::NINGUNA => 'Sin propina',
            self::PORCENTAJE => 'Porcentaje sugerido',
            self::ABIERTA => 'Valor abierto personalizado',
        };
    }
}
