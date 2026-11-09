<?php

namespace App\Actions\Reservas;

use App\Enums\EstadoReserva;
use App\Events\ReservaActualizada;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\EstadoReservaActualizadoNotification;
use Illuminate\Validation\ValidationException;

class CancelarReservaAction
{
    /**
     * Cancela una reserva activa por parte del cliente o de administración (CA-23, RF-RES-08).
     */
    public function handle(Reserva $reserva, string $motivo, ?User $actor = null): Reserva
    {
        if (! $reserva->estaActiva()) {
            throw ValidationException::withMessages([
                'reserva' => 'No es posible cancelar una reserva finalizada o ya cancelada.',
            ]);
        }

        $reserva->estado = EstadoReserva::CANCELADA;
        $reserva->motivo_cancelacion = $motivo;
        $reserva->cancelado_por_id = $actor ? $actor->id : auth()->id();
        $reserva->save();

        event(new ReservaActualizada($reserva, "Reserva cancelada: {$motivo}"));

        $destinatario = $reserva->cliente ?? new User([
            'email' => $reserva->email_contacto,
            'nombre' => $reserva->nombre_contacto,
        ]);
        $destinatario->notify(new EstadoReservaActualizadoNotification($reserva, "Motivo de cancelación: {$motivo}"));

        return $reserva;
    }
}
