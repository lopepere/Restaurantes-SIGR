<?php

namespace App\Http\Controllers\Public;

use App\Actions\Reservas\CrearReservaAction;
use App\Http\Controllers\Controller;
use App\Models\CategoriaReserva;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Services\DisponibilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservaPublicaController extends Controller
{
    /**
     * Calendario interactivo público para agendar reservas (RF-PUB-07).
     */
    public function create(Request $request): Response
    {
        $sucursales = Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'slug', 'direccion', 'telefono']);

        $sucursalSlug = $request->query('sucursal');
        $sucursalActiva = $sucursalSlug
            ? $sucursales->firstWhere('slug', $sucursalSlug)
            : $sucursales->first();

        if (! $sucursalActiva) {
            $sucursalActiva = $sucursales->first();
        }

        $categorias = $sucursalActiva
            ? CategoriaReserva::where('sucursal_id', $sucursalActiva->id)->where('activo', true)->get(['id', 'nombre', 'descripcion'])
            : [];

        return Inertia::render('Public/Reservas', [
            'sucursales' => $sucursales,
            'sucursalActiva' => $sucursalActiva,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Endpoint reactivo que calcula las franjas horarias disponibles según capacidad y horarios (CA-22).
     */
    public function franjas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'fecha' => ['required', 'date'],
            'personas' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $franjas = DisponibilidadService::franjas(
            (int) $validated['sucursal_id'],
            $validated['fecha'],
            (int) $validated['personas']
        );

        return response()->json([
            'franjas' => $franjas,
            'total_disponibles' => count(array_filter($franjas, fn ($f) => $f['disponible'])),
        ]);
    }

    /**
     * Procesa la creación de una reserva.
     */
    public function store(Request $request, CrearReservaAction $crearReservaAction): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'string'],
            'personas' => ['required', 'integer', 'min:1', 'max:50'],
            'categoria_reserva_id' => ['nullable', 'exists:categorias_reserva,id'],
            'nombre_contacto' => ['required', 'string', 'max:100'],
            'email_contacto' => ['required', 'email', 'max:255'],
            'telefono_contacto' => ['required', 'string', 'max:30'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $clienteId = auth()->check() && auth()->user()->hasRole('cliente')
            ? auth()->id()
            : null;

        $reserva = $crearReservaAction->handle([
            'sucursal_id' => (int) $validated['sucursal_id'],
            'fecha' => $validated['fecha'],
            'hora_inicio' => $validated['hora_inicio'],
            'personas' => (int) $validated['personas'],
            'cliente_id' => $clienteId,
            'categoria_reserva_id' => $validated['categoria_reserva_id'] ? (int) $validated['categoria_reserva_id'] : null,
            'nombre_contacto' => $validated['nombre_contacto'],
            'email_contacto' => $validated['email_contacto'],
            'telefono_contacto' => $validated['telefono_contacto'],
            'notas' => $validated['notas'] ?? null,
        ]);

        return redirect()->route('reservas.publico.confirmada', ['codigo' => $reserva->codigo])
            ->with('success', '¡Tu reserva ha sido recibida!');
    }

    /**
     * Pantalla de confirmación de reserva pública.
     */
    public function confirmada(Request $request, string $codigo): Response
    {
        $reserva = Reserva::withoutGlobalScopes()
            ->where('codigo', $codigo)
            ->with(['sucursal', 'espacio', 'categoriaReserva'])
            ->firstOrFail();

        return Inertia::render('Public/ReservaConfirmada', [
            'reserva' => [
                'id' => $reserva->id,
                'codigo' => $reserva->codigo,
                'sucursal' => [
                    'nombre' => $reserva->sucursal->nombre,
                    'direccion' => $reserva->sucursal->direccion,
                    'telefono' => $reserva->sucursal->telefono,
                ],
                'fecha' => $reserva->fecha->format('d/m/Y'),
                'hora_inicio' => substr($reserva->hora_inicio, 0, 5),
                'hora_fin' => substr($reserva->hora_fin, 0, 5),
                'personas' => $reserva->personas,
                'estado' => $reserva->estado->value,
                'estado_etiqueta' => $reserva->estado->etiqueta(),
                'mesa' => $reserva->espacio?->nombre,
                'categoria' => $reserva->categoriaReserva?->nombre,
                'nombre_contacto' => $reserva->nombre_contacto,
                'email_contacto' => $reserva->email_contacto,
                'telefono_contacto' => $reserva->telefono_contacto,
                'notas' => $reserva->notas,
            ],
        ]);
    }
}
