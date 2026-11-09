<?php

namespace App\Http\Controllers\Cajero;

use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ConfiguracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CajeroController extends Controller
{
    /**
     * Consola táctil y operativa exclusiva para el Cajero de turno (Item 6, RF-CAJ).
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $sucursalIds = $user->sucursalIds();
        $sucursalId = (int) (session('sucursal_id') ?? ($sucursalIds[0] ?? 1));

        $sucursal = Sucursal::find($sucursalId) ?? Sucursal::first();

        $cajas = Caja::where('sucursal_id', $sucursalId)->where('activa', true)->get();

        // Turno activo en esta caja/sucursal
        $sesionActiva = SesionCaja::where('sucursal_id', $sucursalId)
            ->where('estado', EstadoSesionCaja::ABIERTA)
            ->with(['caja', 'user'])
            ->orderByDesc('id')
            ->first();

        $resumenSesion = null;
        $pagosSesion = [];

        if ($sesionActiva) {
            $montoEfectivo = (float) Pago::where('sesion_caja_id', $sesionActiva->id)
                ->where('metodo_pago', 'efectivo')
                ->sum('monto');

            $montoElectronico = (float) Pago::where('sesion_caja_id', $sesionActiva->id)
                ->where('metodo_pago', '!=', 'efectivo')
                ->sum('monto');

            $resumenSesion = [
                'id' => $sesionActiva->id,
                'caja_nombre' => $sesionActiva->caja->nombre,
                'cajero' => $sesionActiva->user->nombre,
                'es_mi_turno' => (int) $sesionActiva->user_id === (int) $user->id,
                'fecha_apertura' => $sesionActiva->fecha_apertura->format('d/m/Y H:i'),
                'monto_apertura' => (float) $sesionActiva->monto_apertura,
                'ventas_efectivo' => $montoEfectivo,
                'ventas_electronicas' => $montoElectronico,
                'total_ventas' => $montoEfectivo + $montoElectronico,
                'efectivo_en_caja' => (float) $sesionActiva->monto_apertura + $montoEfectivo,
            ];

            $pagosSesion = Pago::where('sesion_caja_id', $sesionActiva->id)
                ->with(['pedido'])
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (Pago $p) => [
                    'id' => $p->id,
                    'pedido_numero' => $p->pedido->numero,
                    'hora' => $p->created_at->format('H:i'),
                    'metodo' => $p->metodo_pago->etiqueta(),
                    'monto' => (float) $p->monto,
                    'cambio' => (float) $p->cambio_devuelto,
                ]);
        }

        // Comandas pendientes de pago con ítems para acordeón (Item 6 & 11)
        $pedidosPorCobrar = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', false)
            ->where('estado', '!=', EstadoPedido::CANCELADO)
            ->with(['espacio', 'items' => fn ($q) => $q->where('anulado', false)])
            ->orderBy('id')
            ->limit(40)
            ->get()
            ->map(fn (Pedido $ped) => [
                'id' => $ped->id,
                'numero' => $ped->numero,
                'mesa' => $ped->espacio?->nombre,
                'cliente' => $ped->nombre_contacto ?? 'Consumidor Final',
                'tipo' => $ped->tipo->etiqueta(),
                'subtotal' => (float) $ped->subtotal,
                'impuestos' => (float) $ped->impuestos,
                'propina' => (float) $ped->propina,
                'total' => (float) $ped->total,
                'items' => $ped->items->map(fn (PedidoItem $it) => [
                    'id' => $it->id,
                    'nombre' => $it->nombre_item,
                    'cantidad' => (float) $it->cantidad,
                    'precio_unitario' => (float) $it->precio_unitario,
                    'total' => (float) $it->total,
                    'notas' => $it->notas,
                ]),
            ]);

        $baseConfigurada = (float) ConfiguracionService::obtener('caja.base_efectivo_inicial', $sucursalId, 100000.0);

        return Inertia::render('Cajero/Index', [
            'sucursal' => $sucursal,
            'cajas' => $cajas,
            'sesionActiva' => $resumenSesion,
            'pagosSesion' => $pagosSesion,
            'pedidosPorCobrar' => $pedidosPorCobrar,
            'base_efectivo_inicial' => $baseConfigurada,
        ]);
    }

    /**
     * Búsqueda en tiempo real de facturas emitidas para reimpresión o reenvío.
     */
    public function buscarFactura(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $facturas = Comprobante::where('sucursal_id', $sucursalId)
            ->where(function ($q) use ($term) {
                $q->where('numero', 'like', "%{$term}%")
                    ->orWhere('cliente_nombre', 'like', "%{$term}%")
                    ->orWhere('cliente_telefono', 'like', "%{$term}%")
                    ->orWhere('cliente_email', 'like', "%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'numero', 'cliente_nombre', 'total', 'metodo_pago', 'estado', 'created_at']);

        return response()->json($facturas);
    }
}

