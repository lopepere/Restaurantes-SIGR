<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Espacio;
use App\Models\Sucursal;
use App\Models\TipoMesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EspacioController extends Controller
{
    /**
     * Mapa o listado de espacios en tiempo real (RF-RES-11, Prompt A.6).
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

        $espacios = Espacio::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['tipoMesa', 'pedidos' => function ($q) {
                $q->whereNotIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO]);
            }])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Espacio $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'tipo' => $e->tipo,
                'tipo_mesa_id' => $e->tipo_mesa_id,
                'tipo_mesa_nombre' => $e->tipoMesa?->nombre,
                'capacidad' => $e->capacidad,
                'estado' => $e->estado->value,
                'estado_etiqueta' => $e->estado->etiqueta(),
                'activo' => $e->activo,
                'pedido_activo' => $e->pedidos->first() ? [
                    'id' => $e->pedidos->first()->id,
                    'numero' => $e->pedidos->first()->numero,
                    'total' => (float) $e->pedidos->first()->total,
                ] : null,
            ]);

        $tiposMesa = TipoMesa::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'nombre', 'capacidad_minima', 'capacidad_maxima', 'es_abierta']);

        return Inertia::render('Admin/Espacios/Index', [
            'espacios' => $espacios,
            'tiposMesa' => $tiposMesa,
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Crea un nuevo espacio o mesa (RF-RES-01).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Espacio::class);

        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
            'tipo_mesa_id' => ['nullable', 'exists:tipos_mesa,id'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:100'],
            'estado' => ['nullable', 'string'],
        ]);

        $estado = isset($validated['estado'])
            ? (EstadoEspacio::tryFrom($validated['estado']) ?? EstadoEspacio::LIBRE)
            : EstadoEspacio::LIBRE;

        Espacio::create([
            'sucursal_id' => (int) $validated['sucursal_id'],
            'nombre' => $validated['nombre'],
            'tipo' => $validated['tipo'],
            'tipo_mesa_id' => $validated['tipo_mesa_id'] ? (int) $validated['tipo_mesa_id'] : null,
            'capacidad' => (int) $validated['capacidad'],
            'estado' => $estado,
            'activo' => true,
        ]);

        return back()->with('success', 'Espacio creado exitosamente.');
    }

    /**
     * Actualiza un espacio o mesa (RF-RES-02).
     */
    public function update(Request $request, Espacio $espacio): RedirectResponse
    {
        $this->authorize('update', $espacio);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'string', 'max:50'],
            'tipo_mesa_id' => ['nullable', 'exists:tipos_mesa,id'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:100'],
            'estado' => ['required', 'string'],
        ]);

        $estado = EstadoEspacio::tryFrom($validated['estado']) ?? $espacio->estado;

        $espacio->update([
            'nombre' => $validated['nombre'],
            'tipo' => $validated['tipo'],
            'tipo_mesa_id' => $validated['tipo_mesa_id'] ? (int) $validated['tipo_mesa_id'] : null,
            'capacidad' => (int) $validated['capacidad'],
            'estado' => $estado,
        ]);

        return back()->with('success', "Espacio {$espacio->nombre} actualizado.");
    }

    /**
     * Elimina lógicamente un espacio tras validar dependencias activas (RF-AUD-05, RN-GEN-04).
     */
    public function destroy(Espacio $espacio, EliminarConValidacionAction $eliminarAction): RedirectResponse
    {
        $this->authorize('delete', $espacio);

        $eliminarAction->handle(
            $espacio,
            ['pedidos', 'reservas']
        );

        return back()->with('success', "Espacio {$espacio->nombre} enviado a la papelera.");
    }
}
