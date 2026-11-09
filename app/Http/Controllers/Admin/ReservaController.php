<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reservas\AprobarReservaAction;
use App\Actions\Reservas\CancelarReservaAction;
use App\Actions\Reservas\MarcarAsistenciaReservaAction;
use App\Actions\Reservas\ReprogramarReservaAction;
use App\Enums\EstadoReserva;
use App\Http\Controllers\Controller;
use App\Models\CategoriaReserva;
use App\Models\Espacio;
use App\Models\Reserva;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservaController extends Controller
{
    /**
     * Monitor de gestión de reservas de la sucursal con filtros y cambios de estado (RF-RES-01..13).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $esSuperAdmin = $user->hasRole('super_admin');

        $sucursales = $esSuperAdmin
            ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre'])
            : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre']);

        $primeraSucursal = $sucursales->first();
        $primerId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', $primerId));

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a las reservas de esta sucursal.');
        }

        $tab = $request->input('tab', 'proximas');

        $query = Reserva::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['cliente', 'espacio', 'categoriaReserva']);

        // Filtro por pestañas
        match ($tab) {
            'proximas' => $query->whereIn('estado', [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA])
                ->whereDate('fecha', '>=', now()->toDateString()),
            'hoy' => $query->whereDate('fecha', now()->toDateString()),
            'pendientes' => $query->where('estado', EstadoReserva::PENDIENTE),
            'confirmadas' => $query->where('estado', EstadoReserva::CONFIRMADA),
            'historico' => $query->whereIn('estado', [EstadoReserva::CUMPLIDA, EstadoReserva::NO_ASISTIO, EstadoReserva::CANCELADA]),
            'todas' => $query,
            default => $query->whereIn('estado', [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA]),
        };

        if ($filtroFecha = $request->input('fecha')) {
            $query->whereDate('fecha', $filtroFecha);
        }

        if ($filtroEstado = $request->input('estado')) {
            $query->where('estado', $filtroEstado);
        }

        if ($filtroNombre = $request->input('nombre')) {
            $query->where(function ($q) use ($filtroNombre) {
                $q->where('nombre_contacto', 'like', "%{$filtroNombre}%")
                    ->orWhere('telefono_contacto', 'like', "%{$filtroNombre}%")
                    ->orWhere('codigo', 'like', "%{$filtroNombre}%");
            });
        }

        $reservas = $query->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Reserva $r) => [
                'id' => $r->id,
                'codigo' => $r->codigo,
                'fecha' => $r->fecha->format('d/m/Y'),
                'fecha_raw' => $r->fecha->format('Y-m-d'),
                'hora_inicio' => substr($r->hora_inicio, 0, 5),
                'hora_fin' => substr($r->hora_fin, 0, 5),
                'personas' => $r->personas,
                'estado' => $r->estado->value,
                'estado_etiqueta' => $r->estado->etiqueta(),
                'mesa' => $r->espacio?->nombre,
                'espacio_id' => $r->espacio_id,
                'categoria' => $r->categoriaReserva?->nombre,
                'nombre_contacto' => $r->nombre_contacto,
                'telefono_contacto' => $r->telefono_contacto,
                'email_contacto' => $r->email_contacto,
                'notas' => $r->notas,
                'motivo_cancelacion' => $r->motivo_cancelacion,
                'puede_aprobar' => $r->estado === EstadoReserva::PENDIENTE,
                'puede_cancelar' => $r->estaActiva(),
                'puede_reprogramar' => $r->estaActiva(),
                'puede_marcar_asistencia' => $r->estado === EstadoReserva::CONFIRMADA,
            ]);

        $espacios = Espacio::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'nombre', 'capacidad']);
        $categorias = CategoriaReserva::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'nombre']);

        return Inertia::render('Admin/Reservas/Index', [
            'reservas' => $reservas,
            'tab' => $tab,
            'filtros' => $request->only(['tab', 'fecha', 'estado', 'nombre', 'sucursal_id']),
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
            'espacios' => $espacios,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Aprueba una reserva pendiente (RF-RES-07).
     */
    public function aprobar(Request $request, Reserva $reserva, AprobarReservaAction $aprobarAction): RedirectResponse
    {
        $this->authorize('update', $reserva);

        $aprobarAction->handle($reserva);

        return back()->with('success', "Reserva {$reserva->codigo} confirmada exitosamente.");
    }

    /**
     * Cancela una reserva activa (RF-RES-08).
     */
    public function cancelar(Request $request, Reserva $reserva, CancelarReservaAction $cancelarAction): RedirectResponse
    {
        $this->authorize('cancel', $reserva);

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $cancelarAction->handle($reserva, $validated['motivo'], $request->user());

        return back()->with('success', "Reserva {$reserva->codigo} cancelada.");
    }

    /**
     * Reprograma una reserva (RF-RES-09).
     */
    public function reprogramar(Request $request, Reserva $reserva, ReprogramarReservaAction $reprogramarAction): RedirectResponse
    {
        $this->authorize('update', $reserva);

        $validated = $request->validate([
            'nueva_fecha' => ['required', 'date'],
            'nueva_hora_inicio' => ['required', 'string'],
        ]);

        $reprogramarAction->handle($reserva, $validated['nueva_fecha'], $validated['nueva_hora_inicio'], $request->user());

        return back()->with('success', "Reserva {$reserva->codigo} reprogramada con éxito.");
    }

    /**
     * Marca asistencia: cumplida o no asistió (RF-RES-10).
     */
    public function asistencia(Request $request, Reserva $reserva, MarcarAsistenciaReservaAction $asistenciaAction): RedirectResponse
    {
        $this->authorize('update', $reserva);

        $validated = $request->validate([
            'asistio' => ['required', 'boolean'],
        ]);

        $asistenciaAction->handle($reserva, (bool) $validated['asistio']);

        $msg = $validated['asistio'] ? 'Reserva marcada como cumplida.' : 'Reserva marcada como no asistió.';

        return back()->with('success', $msg);
    }
}
