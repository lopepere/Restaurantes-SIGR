<?php

namespace App\Enums;

enum EstadoReserva: string
{
    case PENDIENTE = 'pendiente';
    case CONFIRMADA = 'confirmada';
    case CANCELADA = 'cancelada';
    case CUMPLIDA = 'cumplida';
    case NO_ASISTIO = 'no_asistio';

    /**
     * @return array<int, EstadoReserva>
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PENDIENTE => [self::CONFIRMADA, self::CANCELADA],
            self::CONFIRMADA => [self::CUMPLIDA, self::NO_ASISTIO, self::CANCELADA],
            self::CANCELADA, self::CUMPLIDA, self::NO_ASISTIO => [],
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::CONFIRMADA => 'Confirmada',
            self::CANCELADA => 'Cancelada',
            self::CUMPLIDA => 'Cumplida',
            self::NO_ASISTIO => 'No asistió',
        };
    }
}
