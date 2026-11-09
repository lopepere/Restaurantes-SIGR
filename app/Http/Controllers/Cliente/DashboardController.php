<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dashboard del cliente: promociones vigentes de primeras, pedidos en curso y pedidos recientes (Prompt A.6).
     */
    public function __invoke(Request $request): Response
    {
        $userId = $request->user()->id;

        // 1. Promociones vigentes (primero en el dashboard)
        $promociones = Promocion::where('activa', true)
            ->where('publicada', true)
            ->with(['sucursal'])
            ->get()
            ->filter(fn (Promocion $p): bool => $p->estaVigente())
            ->values()
            ->map(fn (Promocion $p): array => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'beneficio_texto' => $p->beneficioTexto(),
                'sucursal' => $p->sucursal->nombre,
            ]);

        // 2. Pedidos en vivo / activos
        $pedidosActivos = Pedido::withoutGlobalScopes()
            ->where('cliente_id', $userId)
            ->whereNotIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO])
            ->with(['sucursal', 'items'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'sucursal' => $p->sucursal->nombre,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'etiqueta_cliente' => $p->etiquetaCliente(),
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
                'solicito_cancelacion' => $p->solicito_cancelacion,
                'puede_editar_notas' => $p->puedeEditarNotas(),
                'fecha' => $p->fechaLocal(),
            ]);

        // 3. Historial reciente
        $pedidosRecientes = Pedido::withoutGlobalScopes()
            ->where('cliente_id', $userId)
            ->whereIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO])
            ->with(['sucursal', 'items'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'sucursal' => $p->sucursal->nombre,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'etiqueta_cliente' => $p->etiquetaCliente(),
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
                'fecha' => $p->fechaLocal(),
            ]);

        return Inertia::render('Cliente/Dashboard', [
            'promociones' => $promociones,
            'pedidosActivos' => $pedidosActivos,
            'pedidosRecientes' => $pedidosRecientes,
        ]);
    }
}
