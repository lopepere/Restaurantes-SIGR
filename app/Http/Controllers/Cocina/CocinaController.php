<?php

namespace App\Http\Controllers\Cocina;

use App\Actions\Pedidos\ActualizarEstadoPedidoAction;
use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\EstacionCocina;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CocinaController extends Controller
{
    /**
     * Pantalla KDS (Kitchen Display System) en tiempo real para cocineros (Item 6).
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $sucursalIds = $user->sucursalIds();
        $sucursalId = (int) (session('sucursal_id') ?? session('sucursal_activa_id') ?? ($sucursalIds[0] ?? 1));

        $sucursal = Sucursal::find($sucursalId) ?? Sucursal::first();

        $estacionAsignada = null;
        if (Schema::hasTable('estaciones_cocina')) {
            $estacionAsignada = EstacionCocina::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalId)
                ->where('activa', true)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhereNull('user_id');
                })
                ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
                ->first();
        }

        // Pedidos en cocina (Pendiente, Confirmado, En preparación, Listo)
        $pedidosCocina = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereIn('estado', [
                EstadoPedido::PENDIENTE->value,
                EstadoPedido::CONFIRMADO->value,
                EstadoPedido::EN_PREPARACION->value,
                EstadoPedido::LISTO->value,
            ])
            ->whereNull('deleted_at')
            ->with([
                'espacio',
                'mesero',
                'items' => fn ($q) => $q->where('anulado', false),
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'tipo' => $p->tipo->etiqueta(),
                'mesa' => $p->espacio?->nombre,
                'mesero' => $p->mesero->nombre ?? 'Auto-pedido',
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'minutos_transcurridos' => (int) $p->created_at->diffInMinutes(now()),
                'hora' => $p->created_at->format('H:i'),
                'notas' => $p->notas,
                'items' => $p->items->map(fn (PedidoItem $it) => [
                    'id' => $it->id,
                    'nombre' => $it->nombre_item,
                    'cantidad' => (float) $it->cantidad,
                    'notas' => $it->notas,
                    'combo_desglose' => $it->combo_desglose,
                ]),
            ]);

        return Inertia::render('Cocina/Index', [
            'sucursal' => $sucursal,
            'estacion' => $estacionAsignada,
            'pedidos' => $pedidosCocina,
        ]);
    }

    /**
     * Avanza el estado de la comanda en cocina (pendiente/confirmado -> en_preparacion -> listo -> entregado).
     */
    public function cambiarEstado(Request $request, Pedido $pedido, ActualizarEstadoPedidoAction $actualizarEstadoAction): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'string', 'in:en_preparacion,listo,entregado'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->user()?->id;
        $estadoDestino = EstadoPedido::from($validated['estado']);

        // Encadenar pasos intermedios si el pedido aún estaba en PENDIENTE o CONFIRMADO
        if ($pedido->estado === EstadoPedido::PENDIENTE) {
            $pedido = $actualizarEstadoAction->handle($pedido, EstadoPedido::CONFIRMADO, 'Confirmado desde monitor de cocina', $userId);
        }

        if ($estadoDestino === EstadoPedido::LISTO && $pedido->estado === EstadoPedido::CONFIRMADO) {
            $pedido = $actualizarEstadoAction->handle($pedido, EstadoPedido::EN_PREPARACION, 'Preparación iniciada en cocina', $userId);
        }

        if ($estadoDestino === EstadoPedido::ENTREGADO) {
            if ($pedido->estado === EstadoPedido::CONFIRMADO) {
                $pedido = $actualizarEstadoAction->handle($pedido, EstadoPedido::EN_PREPARACION, 'Preparación iniciada en cocina', $userId);
            }
            if ($pedido->estado === EstadoPedido::EN_PREPARACION) {
                $pedido = $actualizarEstadoAction->handle($pedido, EstadoPedido::LISTO, 'Listo para despacho en cocina', $userId);
            }
        }

        $actualizarEstadoAction->handle($pedido, $estadoDestino, $validated['motivo'] ?? null, $userId);

        return back()->with('success', "Pedido #{$pedido->numero} marcado como '{$estadoDestino->etiqueta()}'.");
    }
}
