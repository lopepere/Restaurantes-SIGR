<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pedidos\ActualizarEstadoPedidoAction;
use App\Actions\Pedidos\ResolverCancelacionAction;
use App\Enums\EstadoPedido;
use App\Enums\TipoPedido;
use App\Http\Controllers\Controller;
use App\Models\Espacio;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    /**
     * Monitor de pedidos en tiempo real con pestañas, acordeón y filtros completos (Prompt A.6).
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
            abort(403, 'No tienes autorización para acceder a los pedidos de esta sucursal.');
        }

        $tab = $request->input('tab', 'en_curso');

        $query = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['sucursal', 'cliente', 'mesero', 'espacio', 'items.itemMenu', 'estados']);

        // Aplicación de pestañas principales (Prompt A.6)
        match ($tab) {
            'en_curso' => $query->whereIn('estado', [
                EstadoPedido::PENDIENTE,
                EstadoPedido::CONFIRMADO,
                EstadoPedido::EN_PREPARACION,
                EstadoPedido::LISTO,
                EstadoPedido::CANCELACION_SOLICITADA,
            ]),
            'solicitudes_cancelacion' => $query->where(function ($q) {
                $q->where('solicito_cancelacion', true)
                    ->orWhere('estado', EstadoPedido::CANCELACION_SOLICITADA);
            }),
            'domicilios' => $query->where('tipo', TipoPedido::DOMICILIO),
            'para_llevar' => $query->where('tipo', TipoPedido::PARA_LLEVAR),
            'en_sitio' => $query->where('tipo', TipoPedido::EN_SITIO),
            'todos' => $query,
            default => $query->whereIn('estado', [
                EstadoPedido::PENDIENTE,
                EstadoPedido::CONFIRMADO,
                EstadoPedido::EN_PREPARACION,
                EstadoPedido::LISTO,
            ]),
        };

        // Filtros detallados de búsqueda (A.6)
        if ($estadoFiltro = $request->input('estado')) {
            $query->where('estado', $estadoFiltro);
        }

        if ($numeroFiltro = $request->input('numero')) {
            $query->where('numero', 'like', "%{$numeroFiltro}%");
        }

        if ($nombreFiltro = $request->input('nombre')) {
            $query->where(function ($q) use ($nombreFiltro) {
                $q->where('nombre_contacto', 'like', "%{$nombreFiltro}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$nombreFiltro}%"));
            });
        }

        if ($telefonoFiltro = $request->input('telefono')) {
            $query->where(function ($q) use ($telefonoFiltro) {
                $q->where('telefono_contacto', 'like', "%{$telefonoFiltro}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('telefono', 'like', "%{$telefonoFiltro}%"));
            });
        }

        if ($tipoFiltro = $request->input('tipo')) {
            $query->where('tipo', $tipoFiltro);
        }

        if ($meseroFiltro = $request->input('mesero_id')) {
            $query->where('mesero_id', $meseroFiltro);
        }

        if ($espacioFiltro = $request->input('espacio_id')) {
            $query->where('espacio_id', $espacioFiltro);
        }

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }

        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }

        $pedidos = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'tipo' => $p->tipo->value,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'transiciones_permitidas' => array_map(fn ($e) => [
                    'value' => $e->value,
                    'etiqueta' => $e->etiqueta(),
                ], $p->estado->transicionesPermitidas()),
                'cliente_nombre' => $p->cliente ? $p->cliente->nombre : ($p->nombre_contacto ?? 'Consumidor Final'),
                'cliente_telefono' => $p->cliente ? $p->cliente->telefono : ($p->telefono_contacto ?? ''),
                'direccion_entrega' => $p->direccion_entrega,
                'mesero_nombre' => $p->mesero ? $p->mesero->nombre : null,
                'mesa' => $p->espacio ? $p->espacio->nombre : null,
                'subtotal' => (float) $p->subtotal,
                'descuento' => (float) $p->descuento,
                'impuestos' => (float) $p->impuestos,
                'propina' => (float) $p->propina,
                'total' => (float) $p->total,
                'notas' => $p->notas,
                'solicito_cancelacion' => $p->solicito_cancelacion,
                'motivo_cancelacion' => $p->motivo_cancelacion,
                'pagado' => $p->pagado,
                'fecha' => $p->fechaLocal(),
                'items' => $p->items->map(fn ($it) => [
                    'id' => $it->id,
                    'nombre' => $it->nombre_item,
                    'cantidad' => (float) $it->cantidad,
                    'precio_unitario' => (float) $it->precio_unitario,
                    'total' => (float) $it->total,
                    'notas' => $it->notas,
                ]),
                'estados' => $p->estados->map(fn ($es) => [
                    'estado' => $es->estado_nuevo,
                    'motivo' => $es->motivo,
                    'hora' => $es->created_at->format('H:i'),
                ]),
            ]);

        $meseros = User::whereHas('sucursales', fn ($q) => $q->where('sucursales.id', $sucursalId))
            ->role('mesero')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $espacios = Espacio::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo']);

        return Inertia::render('Admin/Pedidos/Index', [
            'pedidos' => $pedidos,
            'tab' => $tab,
            'filtros' => $request->only([
                'tab', 'estado', 'numero', 'nombre', 'telefono', 'tipo', 'mesero_id', 'espacio_id', 'fecha_inicio', 'fecha_fin', 'sucursal_id',
            ]),
            'sucursal_id' => $sucursalId,
            'sucursales' => $sucursales,
            'meseros' => $meseros,
            'espacios' => $espacios,
        ]);
    }

    /**
     * Cambia el estado del pedido siguiendo las transiciones autorizadas de la ERS 8.3.
     */
    public function cambiarEstado(
        Request $request,
        Pedido $pedido,
        ActualizarEstadoPedidoAction $actualizarEstadoPedidoAction
    ): RedirectResponse {
        $this->authorize('update', $pedido);

        $validated = $request->validate([
            'nuevo_estado' => ['required', 'string'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $nuevoEstado = EstadoPedido::from($validated['nuevo_estado']);

        $actualizarEstadoPedidoAction->handle(
            $pedido,
            $nuevoEstado,
            $validated['motivo'] ?? null,
            $request->user()->id
        );

        return back()->with('success', "Estado del pedido {$pedido->numero} actualizado a {$nuevoEstado->etiqueta()}.");
    }

    /**
     * Resuelve una solicitud de cancelación (aprobar o rechazar) (CA-09).
     */
    public function resolverCancelacion(
        Request $request,
        Pedido $pedido,
        ResolverCancelacionAction $resolverCancelacionAction
    ): RedirectResponse {
        $this->authorize('cancel', $pedido);

        $validated = $request->validate([
            'aprobar' => ['required', 'boolean'],
            'respuesta_admin' => ['nullable', 'string', 'max:500'],
        ]);

        $resolverCancelacionAction->handle(
            $pedido,
            (bool) $validated['aprobar'],
            $validated['respuesta_admin'] ?? null,
            $request->user()
        );

        $mensaje = $validated['aprobar']
            ? "Cancelación del pedido {$pedido->numero} aprobada e inventario repuesto."
            : "Cancelación del pedido {$pedido->numero} rechazada; el pedido vuelve a cocina.";

        return back()->with('success', $mensaje);
    }
}
