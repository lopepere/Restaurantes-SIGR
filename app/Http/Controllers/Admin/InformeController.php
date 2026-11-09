<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoPedido;
use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeController extends Controller
{
    /**
     * Tablero de informes ejecutivos y métricas de operación en tiempo real (RF-INF, CA-38..40).
     */
    public function index(Request $request): Response
    {
        $datos = $this->obtenerDatosInforme($request);

        return Inertia::render('Admin/Informes/Index', [
            'metricas' => $datos['metricas'],
            'ventasPorModalidad' => $datos['ventasPorModalidad'],
            'topProductos' => $datos['topProductos'],
            'periodo' => $datos['periodo'],
            'fecha_inicio' => $datos['fecha_inicio_str'],
            'fecha_fin' => $datos['fecha_fin_str'],
            'sucursales' => $datos['sucursales'],
            'sucursal_id' => $datos['sucursal_id'],
        ]);
    }

    /**
     * Exporta el informe de operación a formato Excel (.CSV compatible con UTF-8 BOM).
     */
    public function exportarExcel(Request $request): StreamedResponse
    {
        $datos = $this->obtenerDatosInforme($request);
        $sucursal = $datos['sucursal'];
        $nombreArchivo = 'informe_ventas_'.($sucursal ? $sucursal->slug : 'sucursal').'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($datos) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM para apertura nativa e impecable en Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Encabezado del restaurante
            fputcsv($handle, ['SIGR - SISTEMA INTEGRAL DE GESTIÓN DE RESTAURANTE']);
            fputcsv($handle, ['SUCURSAL:', $datos['sucursal']->nombre ?? 'N/A']);
            fputcsv($handle, ['RANGO:', $datos['periodo_label']]);
            fputcsv($handle, ['FECHA GENERACIÓN:', now()->format('d/m/Y H:i:s')]);
            fputcsv($handle, []);

            // Resumen Ejecutivo
            fputcsv($handle, ['=== RESUMEN EJECUTIVO DE VENTAS Y OPERACIÓN ===']);
            fputcsv($handle, ['Métrica', 'Valor']);
            fputcsv($handle, ['Ventas Totales Facturadas', '$ '.number_format($datos['metricas']['totalVentas'], 2, ',', '.')]);
            fputcsv($handle, ['Base Imponible / Subtotal', '$ '.number_format($datos['metricas']['totalSubtotal'], 2, ',', '.')]);
            fputcsv($handle, ['Impuestos Recaudados (INC/IVA)', '$ '.number_format($datos['metricas']['totalImpuestos'], 2, ',', '.')]);
            fputcsv($handle, ['Propinas Recaudadas', '$ '.number_format($datos['metricas']['totalPropinas'], 2, ',', '.')]);
            fputcsv($handle, ['Total Comandas Pagadas', $datos['metricas']['totalPedidos'].' pedidos']);
            fputcsv($handle, ['Compras a Proveedores', '$ '.number_format($datos['metricas']['totalCompras'], 2, ',', '.')]);
            fputcsv($handle, ['Descuadre Acumulado Cajas', '$ '.number_format($datos['metricas']['descuadreCajas'], 2, ',', '.')]);
            fputcsv($handle, []);

            // Ventas por Modalidad
            fputcsv($handle, ['=== VENTAS POR MODALIDAD ===']);
            fputcsv($handle, ['Modalidad', 'Cantidad Órdenes', 'Total Facturado ($)']);
            foreach ($datos['ventasPorModalidad'] as $mod) {
                fputcsv($handle, [$mod['etiqueta'], $mod['cantidad'], number_format($mod['total'], 2, ',', '.')]);
            }
            fputcsv($handle, []);

            // Top Platos
            fputcsv($handle, ['=== PLATOS Y BEBIDAS MÁS VENDIDOS ===']);
            fputcsv($handle, ['#', 'Producto', 'Unidades Vendidas', 'Total Ventas ($)']);
            foreach ($datos['topProductos'] as $idx => $prod) {
                fputcsv($handle, [$idx + 1, $prod['nombre'], $prod['cantidad'], number_format($prod['total'], 2, ',', '.')]);
            }

            fclose($handle);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    /**
     * Exporta el informe de operación a documento PDF formal.
     */
    public function exportarPdf(Request $request): HttpResponse
    {
        $datos = $this->obtenerDatosInforme($request);
        $sucursal = $datos['sucursal'];
        $nombreArchivo = 'informe_ventas_'.($sucursal ? $sucursal->slug : 'sucursal').'_'.now()->format('Ymd_His').'.pdf';

        $pdf = Pdf::loadView('pdf.informe', $datos);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($nombreArchivo);
    }

    /**
     * Helper centralizado para consultar y preparar las métricas operativas del informe.
     *
     * @return array<string, mixed>
     */
    protected function obtenerDatosInforme(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $esSuperAdmin = $user->hasRole('super_admin');

        $sucursales = $esSuperAdmin
            ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'slug', 'direccion', 'telefono'])
            : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre', 'sucursales.slug', 'sucursales.direccion', 'sucursales.telefono']);

        $primeraSucursal = $sucursales->first();
        $primerId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_id', session('sucursal_activa_id', $primerId)));

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a los informes de esta sucursal.');
        }

        session([
            'sucursal_id' => $sucursalId,
            'sucursal_activa_id' => $sucursalId,
        ]);

        $sucursal = Sucursal::find($sucursalId) ?? $primeraSucursal;

        // Rango de fechas
        $periodo = (string) $request->input('periodo', 'hoy');
        $fechaInicioStr = $request->input('fecha_inicio');
        $fechaFinStr = $request->input('fecha_fin');

        if ($periodo === 'personalizado' && $fechaInicioStr) {
            $fechaInicio = Carbon::parse($fechaInicioStr)->startOfDay();
            $fechaFin = $fechaFinStr ? Carbon::parse($fechaFinStr)->endOfDay() : Carbon::parse($fechaInicioStr)->endOfDay();
            $periodoLabel = "Desde {$fechaInicio->format('d/m/Y')} hasta {$fechaFin->format('d/m/Y')}";
        } else {
            $fechaFin = Carbon::now()->endOfDay();
            $fechaInicio = match ($periodo) {
                'hoy' => Carbon::today()->startOfDay(),
                'semana' => Carbon::now()->subDays(7)->startOfDay(),
                'mes' => Carbon::now()->subDays(30)->startOfDay(),
                'ano' => Carbon::now()->startOfYear(),
                default => Carbon::today()->startOfDay(),
            };
            $periodoLabel = match ($periodo) {
                'hoy' => 'Hoy ('.Carbon::today()->format('d/m/Y').')',
                'semana' => 'Últimos 7 días',
                'mes' => 'Últimos 30 días',
                'ano' => 'Año en curso',
                default => 'Hoy',
            };
        }

        // Ventas consolidadas
        $pedidosPagados = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', true)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

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
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
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
            ->whereBetween('fecha_compra', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->sum('total');

        // Descuadre en cierres de caja
        $descuadreCajas = (float) SesionCaja::where('sucursal_id', $sucursalId)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('diferencia');

        // Top 5 platos más vendidos
        $topProductos = DB::table('pedido_items')
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->where('pedidos.sucursal_id', $sucursalId)
            ->where('pedidos.pagado', true)
            ->whereNull('pedidos.deleted_at')
            ->whereBetween('pedidos.created_at', [$fechaInicio, $fechaFin])
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

        return [
            'sucursal' => $sucursal,
            'sucursal_id' => $sucursalId,
            'sucursales' => $sucursales,
            'periodo' => $periodo,
            'periodo_label' => $periodoLabel,
            'fecha_inicio_str' => $fechaInicio->format('Y-m-d'),
            'fecha_fin_str' => $fechaFin->format('Y-m-d'),
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
        ];
    }
}
