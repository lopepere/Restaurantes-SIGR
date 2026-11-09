<?php

namespace App\Enums;

enum EstadoEspacio: string
{
    case LIBRE = 'libre';
    case OCUPADO = 'ocupado';
    case RESERVADO = 'reservado';
    case FUERA_SERVICIO = 'fuera_servicio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::LIBRE => 'Libre',
            self::OCUPADO => 'Ocupado',
            self::RESERVADO => 'Reservado',
            self::FUERA_SERVICIO => 'Fuera de servicio',
        };
    }
}
