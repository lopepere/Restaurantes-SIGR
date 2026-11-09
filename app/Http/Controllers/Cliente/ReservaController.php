<?php

namespace App\Http\Controllers\Cliente;

use App\Actions\Reservas\CancelarReservaAction;
use App\Http\Controllers\Controller;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservaController extends Controller
{
    /**
     * Listado de reservas del cliente (activas e histórico).
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $reservas = Reserva::withoutGlobalScopes()
            ->where('cliente_id', $userId)
            ->with(['sucursal', 'espacio', 'categoriaReserva'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->paginate(15)
            ->through(fn (Reserva $r) => [
                'id' => $r->id,
                'codigo' => $r->codigo,
                'sucursal' => $r->sucursal->nombre,
                'fecha' => $r->fecha->format('d/m/Y'),
                'hora' => substr($r->hora_inicio, 0, 5).' - '.substr($r->hora_fin, 0, 5),
                'personas' => $r->personas,
                'estado' => $r->estado->value,
                'estado_etiqueta' => $r->estado->etiqueta(),
                'mesa' => $r->espacio?->nombre,
                'categoria' => $r->categoriaReserva?->nombre,
                'notas' => $r->notas,
                'puede_cancelar' => $r->estaActiva(),
                'motivo_cancelacion' => $r->motivo_cancelacion,
            ]);

        return Inertia::render('Cliente/Reservas/Index', [
            'reservas' => $reservas,
        ]);
    }

    /**
     * Cancela una reserva propia del cliente (CA-23).
     */
    public function cancelar(Request $request, Reserva $reserva, CancelarReservaAction $cancelarAction): RedirectResponse
    {
        $this->authorize('cancel', $reserva);

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $cancelarAction->handle($reserva, $validated['motivo'], $request->user());

        return back()->with('success', 'Tu reserva ha sido cancelada correctamente.');
    }
}
