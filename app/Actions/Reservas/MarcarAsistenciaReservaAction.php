<?php

namespace App\Actions\Reservas;

use App\Enums\EstadoReserva;
use App\Events\ReservaActualizada;
use App\Models\Reserva;
use Illuminate\Validation\ValidationException;

class MarcarAsistenciaReservaAction
{
    /**
     * Marca una reserva como CUMPLIDA o NO_ASISTIO (RF-RES-10).
     */
    public function handle(Reserva $reserva, bool $asistio): Reserva
    {
        if ($reserva->estado !== EstadoReserva::CONFIRMADA) {
            throw ValidationException::withMessages([
                'reserva' => 'Solo se puede marcar asistencia sobre reservas que hayan sido confirmadas.',
            ]);
        }

        $reserva->estado = $asistio ? EstadoReserva::CUMPLIDA : EstadoReserva::NO_ASISTIO;
        $reserva->save();

        event(new ReservaActualizada($reserva, $asistio ? 'Marcada como cumplida' : 'Marcada como no asistió'));

        return $reserva;
    }
}
