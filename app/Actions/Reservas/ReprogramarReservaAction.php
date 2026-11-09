<?php

namespace App\Actions\Reservas;

use App\Events\ReservaActualizada;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\EstadoReservaActualizadoNotification;
use App\Services\ConfiguracionService;
use App\Services\DisponibilidadService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReprogramarReservaAction
{
    /**
     * Reprograma una reserva existente verificando disponibilidad para el nuevo horario (CA-23, RF-RES-09).
     */
    public function handle(Reserva $reserva, string $nuevaFecha, string $nuevaHoraInicio, ?User $actor = null): Reserva
    {
        if (! $reserva->estaActiva()) {
            throw ValidationException::withMessages([
                'reserva' => 'Solo se pueden reprogramar reservas que estén pendientes o confirmadas.',
            ]);
        }

        $sucursalId = $reserva->sucursal_id;
        $duracion = (int) ConfiguracionService::obtener('reservas.duracion_minutos', $sucursalId, 120);
        $nuevaHoraFin = Carbon::parse("{$nuevaFecha} {$nuevaHoraInicio}")->addMinutes($duracion)->format('H:i:s');

        $nuevoEspacio = DisponibilidadService::asignarMejorEspacio(
            $sucursalId,
            $nuevaFecha,
            $nuevaHoraInicio,
            $nuevaHoraFin,
            $reserva->personas
        );

        if (! $nuevoEspacio) {
            throw ValidationException::withMessages([
                'nueva_hora_inicio' => 'No hay disponibilidad para reprogramar en la fecha y hora seleccionadas.',
            ]);
        }

        $reserva->fecha = Carbon::parse($nuevaFecha);
        $reserva->hora_inicio = $nuevaHoraInicio;
        $reserva->hora_fin = $nuevaHoraFin;
        $reserva->espacio_id = $nuevoEspacio->id;
        $reserva->save();

        event(new ReservaActualizada($reserva, "Reprogramada para el {$nuevaFecha} a las {$nuevaHoraInicio}"));

        $destinatario = $reserva->cliente ?? new User([
            'email' => $reserva->email_contacto,
            'nombre' => $reserva->nombre_contacto,
        ]);
        $destinatario->notify(new EstadoReservaActualizadoNotification(
            $reserva,
            "Tu reserva ha sido reprogramada para el {$nuevaFecha} a las {$nuevaHoraInicio}."
        ));

        return $reserva;
    }
}
