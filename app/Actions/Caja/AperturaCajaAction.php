<?php

namespace App\Actions\Caja;

use App\Enums\EstadoSesionCaja;
use App\Models\Caja;
use App\Models\SesionCaja;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AperturaCajaAction
{
    /**
     * Abre un nuevo turno o sesión de caja con base inicial en efectivo (RF-CAJ-01..03, CA-29).
     */
    public function handle(Caja $caja, float $montoApertura, ?User $actor = null): SesionCaja
    {
        $sesionActiva = SesionCaja::where('caja_id', $caja->id)
            ->where('estado', EstadoSesionCaja::ABIERTA)
            ->first();

        if ($sesionActiva) {
            throw ValidationException::withMessages([
                'caja' => "La caja '{$caja->nombre}' ya cuenta con una sesión abierta (ID #{$sesionActiva->id}).",
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? 1);

        return SesionCaja::create([
            'caja_id' => $caja->id,
            'sucursal_id' => $caja->sucursal_id,
            'user_id' => $userId,
            'fecha_apertura' => now(),
            'monto_apertura' => max(0.0, $montoApertura),
            'estado' => EstadoSesionCaja::ABIERTA,
        ]);
    }
}
