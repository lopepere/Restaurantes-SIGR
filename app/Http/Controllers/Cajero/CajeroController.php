<?php

namespace App\Http\Controllers\Cajero;

use App\Actions\Comprobantes\ReenviarComprobanteAction;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Http\Controllers\Controller;
use App\Models\AsignacionBaseCaja;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        $sucursalId = (int) (session('sucursal_id') ?? session('sucursal_activa_id') ?? ($sucursalIds[0] ?? 1));

        $sucursal = Sucursal::find($sucursalId) ?? Sucursal::first();

        $cajas = Caja::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activa', true)
            ->with('cajero:id,nombre,email')
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->get();

        $asignacionesHoy = [];
        if (Schema::hasTable('asignaciones_base_caja')) {
            $asignacionesHoy = AsignacionBaseCaja::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalId)
                ->whereDate('fecha', now()->toDateString())
                ->orderByDesc('id')
                ->get()
                ->keyBy('caja_id')
                ->map(fn (AsignacionBaseCaja $a) => (float) $a->monto)
                ->all();
        }

        // Turno activo en esta caja/sucursal
        $sesionActiva = SesionCaja::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
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
                    'referencia_transaccion' => $p->referencia_transaccion,
                    'nota_adicional' => $p->nota_adicional,
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

        $primeraCajaId = $cajas->first()?->id;
        $baseConfigurada = ($primeraCajaId && isset($asignacionesHoy[$primeraCajaId]))
            ? (float) $asignacionesHoy[$primeraCajaId]
            : (float) ConfiguracionService::obtener('caja.base_efectivo_inicial', $sucursalId, 100000.0);

        return Inertia::render('Cajero/Index', [
            'sucursal' => $sucursal,
            'cajas' => $cajas,
            'asignacionesHoy' => $asignacionesHoy,
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
        $sucursalId = (int) (session('sucursal_id') ?? session('sucursal_activa_id') ?? ($user->sucursalIds()[0] ?? 1));

        $facturas = Comprobante::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where(function ($q) use ($term) {
                $q->where('numero', 'like', "%{$term}%")
                    ->orWhere('cliente_nombre', 'like', "%{$term}%")
                    ->orWhere('cliente_telefono', 'like', "%{$term}%")
                    ->orWhere('cliente_email', 'like', "%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'numero', 'codigo_verificacion', 'cliente_nombre', 'cliente_email', 'total', 'metodo_pago', 'estado', 'created_at']);

        return response()->json($facturas);
    }

    /**
     * Reenvía comprobante desde la consola de cajero.
     */
    public function reenviarFactura(Request $request, Comprobante $comprobante, ReenviarComprobanteAction $reenviarAction): RedirectResponse
    {
        $correos = $request->input('correos');
        if (empty($correos)) {
            $correoDestino = $comprobante->cliente_email ?: 'cliente@sigr.test';
            $correos = [$correoDestino];
        }

        $reenviarAction->handle($comprobante, (array) $correos, $request->input('mensaje'), $request->user());

        return back()->with('success', "Comprobante {$comprobante->numero} reenviado exitosamente.");
    }
}
