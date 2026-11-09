<?php

namespace App\Actions\Reservas;

use App\Enums\EstadoReserva;
use App\Events\ReservaActualizada;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\EstadoReservaActualizadoNotification;
use Illuminate\Validation\ValidationException;

class AprobarReservaAction
{
    /**
     * Aprueba una reserva pendiente (RF-RES-07).
     */
    public function handle(Reserva $reserva): Reserva
    {
        if ($reserva->estado !== EstadoReserva::PENDIENTE) {
            throw ValidationException::withMessages([
                'reserva' => 'Solo se pueden aprobar reservas que se encuentren en estado pendiente.',
            ]);
        }

        $reserva->estado = EstadoReserva::CONFIRMADA;
        $reserva->save();

        event(new ReservaActualizada($reserva, 'Reserva aprobada por administración'));

        $destinatario = $reserva->cliente ?? new User([
            'email' => $reserva->email_contacto,
            'nombre' => $reserva->nombre_contacto,
        ]);
        $destinatario->notify(new EstadoReservaActualizadoNotification($reserva, 'Tu reserva ha sido confirmada con éxito.'));

        return $reserva;
    }
}
