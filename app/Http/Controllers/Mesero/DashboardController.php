<?php

namespace App\Http\Controllers\Mesero;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Espacio;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dashboard del mesero: comandas propias destacadas, pedidos para llevar/domicilio y estado de mesas.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $sucursalId = (int) session('sucursal_activa_id', $user->sucursalIds()[0] ?? 1);

        $pedidosActivos = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereNotIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO])
            ->with(['cliente', 'espacio', 'items'])
            ->orderByDesc('created_at')
            ->get();

        $pedidosPropios = $pedidosActivos
            ->where('mesero_id', $user->id)
            ->values()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'mesa' => $p->espacio?->nombre,
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
            ]);

        $pedidosSucursal = $pedidosActivos
            ->where('mesero_id', '!=', $user->id)
            ->values()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'mesa' => $p->espacio?->nombre,
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
            ]);

        $mesasOcupadasCount = Espacio::where('sucursal_id', $sucursalId)
            ->where('estado', 'ocupado')
            ->count();

        $totalMesas = Espacio::where('sucursal_id', $sucursalId)->count();

        return Inertia::render('Mesero/Dashboard', [
            'pedidosPropios' => $pedidosPropios,
            'pedidosSucursal' => $pedidosSucursal,
            'mesasOcupadas' => $mesasOcupadasCount,
            'totalMesas' => $totalMesas,
            'sucursal_id' => $sucursalId,
        ]);
    }
}
