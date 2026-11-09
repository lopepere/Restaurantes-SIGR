<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoPedido;
use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InformeController extends Controller
{
    /**
     * Tablero de informes ejecutivos y métricas de operación en tiempo real (RF-INF, CA-38..40).
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
            abort(403, 'No tienes autorización para acceder a los informes de esta sucursal.');
        }

        $periodo = $request->input('periodo', 'hoy');
        $fechaInicio = match ($periodo) {
            'hoy' => Carbon::today()->startOfDay(),
            'semana' => Carbon::now()->subDays(7)->startOfDay(),
            'mes' => Carbon::now()->subDays(30)->startOfDay(),
            default => Carbon::today()->startOfDay(),
        };

        // Ventas consolidadas
        $pedidosPagados = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', true)
            ->where('created_at', '>=', $fechaInicio);

        $totalVentas = (float) (clone $pedidosPagados)->sum('total');
        $totalSubtotal = (float) (clone $pedidosPagados)->sum('subtotal');
        $totalImpuestos = (float) (clone $pedidosPagados)->sum('impuestos');
        $totalPropinas = (float) (clone $pedidosPagados)->sum('propina');
        $totalPedidos = (clone $pedidosPagados)->count();

        // Ventas por modalidad
        $ventasPorModalidad = DB::table('pedidos')
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', true)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $fechaInicio)
            ->select('tipo', DB::raw('count(*) as total_count'), DB::raw('sum(total) as suma_total'))
            ->groupBy('tipo')
            ->get()
            ->map(function ($row) {
                $tipoEnum = TipoPedido::tryFrom((string) $row->tipo);

                return [
                    'tipo' => (string) $row->tipo,
                    'etiqueta' => $tipoEnum ? $tipoEnum->etiqueta() : (string) $row->tipo,
                    'cantidad' => (int) $row->total_count,
                    'total' => (float) $row->suma_total,
                ];
            });

        // Compras a proveedores
        $totalCompras = (float) Compra::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('estado', 'registrada')
            ->where('fecha_compra', '>=', $fechaInicio->toDateString())
            ->sum('total');

        // Descuadre en cierres de caja
        $descuadreCajas = (float) SesionCaja::where('sucursal_id', $sucursalId)
            ->where('created_at', '>=', $fechaInicio)
            ->sum('diferencia');

        // Top 5 platos más vendidos
        $topProductos = DB::table('pedido_items')
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->where('pedidos.sucursal_id', $sucursalId)
            ->where('pedidos.pagado', true)
            ->whereNull('pedidos.deleted_at')
            ->where('pedidos.created_at', '>=', $fechaInicio)
            ->select('pedido_items.nombre_item', DB::raw('sum(pedido_items.cantidad) as total_cantidad'), DB::raw('sum(pedido_items.total) as total_ventas'))
            ->groupBy('pedido_items.nombre_item')
            ->orderByDesc('total_cantidad')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'nombre' => (string) $p->nombre_item,
                'cantidad' => (float) $p->total_cantidad,
                'total' => (float) $p->total_ventas,
            ]);

        return Inertia::render('Admin/Informes/Index', [
            'metricas' => [
                'totalVentas' => $totalVentas,
                'totalSubtotal' => $totalSubtotal,
                'totalImpuestos' => $totalImpuestos,
                'totalPropinas' => $totalPropinas,
                'totalPedidos' => $totalPedidos,
                'totalCompras' => $totalCompras,
                'descuadreCajas' => $descuadreCajas,
            ],
            'ventasPorModalidad' => $ventasPorModalidad,
            'topProductos' => $topProductos,
            'periodo' => $periodo,
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }
}
