<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Caja\CierreCajaAction;
use App\Actions\Caja\RegistrarPagoAction;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Http\Controllers\Controller;
use App\Models\AsignacionBaseCaja;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\DevolucionCompra;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Services\ConfiguracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CajaController extends Controller
{
    /**
     * Módulo de caja, turnos, arqueo y registro de pagos (RF-CAJ-01..08, CA-29, CA-30).
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
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_id', session('sucursal_activa_id', $primerId)));

        session([
            'sucursal_id' => $sucursalId,
            'sucursal_activa_id' => $sucursalId,
        ]);

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a las cajas de esta sucursal.');
        }

        $cajas = Caja::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activa', true)
            ->with('cajero:id,nombre,email')
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

        // Sesión activa actual en la sucursal
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

        // Pedidos pendientes de pago en la sucursal (con desglose de ítems para acordeón)
        $pedidosPorCobrar = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', false)
            ->where('estado', '!=', EstadoPedido::CANCELADO)
            ->with(['espacio', 'items' => fn ($q) => $q->where('anulado', false)])
            ->orderBy('id')
            ->limit(30)
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

        // Facturas recientemente anuladas y devoluciones en tiempo real (Item 11)
        $facturasAnuladasRecientes = Comprobante::where('sucursal_id', $sucursalId)
            ->where('estado', 'anulado')
            ->with(['anuladoPor'])
            ->orderByDesc('anulado_at')
            ->limit(10)
            ->get()
            ->map(fn (Comprobante $c) => [
                'id' => $c->id,
                'numero' => $c->numero,
                'cliente' => $c->cliente_nombre,
                'total' => (float) $c->total,
                'anulado_at' => $c->anulado_at ? $c->anulado_at->format('d/m H:i') : '-',
                'motivo' => $c->motivo_anulacion,
                'anulado_por' => $c->anuladoPor->nombre ?? 'Sistema',
            ]);

        $devolucionesRecientes = DevolucionCompra::where('sucursal_id', $sucursalId)
            ->with(['compra', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (DevolucionCompra $d) => [
                'id' => $d->id,
                'factura_compra' => $d->compra?->numero_factura,
                'total_devuelto' => (float) $d->total_devuelto,
                'tipo' => $d->tipo,
                'motivo' => $d->motivo,
                'fecha' => $d->fecha_devolucion->format('d/m H:i'),
                'usuario' => $d->user?->nombre,
            ]);

        // Historial de cierres de caja recientes
        $cierresRecientes = SesionCaja::where('sucursal_id', $sucursalId)
            ->where('estado', EstadoSesionCaja::CERRADA)
            ->with(['caja', 'user'])
            ->orderByDesc('fecha_cierre')
            ->limit(10)
            ->get()
            ->map(fn (SesionCaja $s) => [
                'id' => $s->id,
                'caja' => $s->caja->nombre,
                'cajero' => $s->user->nombre,
                'apertura' => $s->fecha_apertura->format('d/m H:i'),
                'cierre' => $s->fecha_cierre ? $s->fecha_cierre->format('d/m H:i') : '-',
                'monto_apertura' => (float) $s->monto_apertura,
                'monto_declarado' => (float) $s->monto_cierre_declarado,
                'monto_calculado' => (float) $s->monto_cierre_calculado,
                'diferencia' => (float) $s->diferencia,
            ]);

        $primeraCajaId = $cajas->first()?->id;
        $baseConfigurada = ($primeraCajaId && isset($asignacionesHoy[$primeraCajaId]))
            ? (float) $asignacionesHoy[$primeraCajaId]
            : (float) ConfiguracionService::obtener('caja.base_efectivo_inicial', $sucursalId, 100000.0);

        return Inertia::render('Admin/Caja/Index', [
            'cajas' => $cajas,
            'asignacionesHoy' => $asignacionesHoy,
            'sesionActiva' => $resumenSesion,
            'pagosSesion' => $pagosSesion,
            'pedidosPorCobrar' => $pedidosPorCobrar,
            'cierresRecientes' => $cierresRecientes,
            'facturasAnuladasRecientes' => $facturasAnuladasRecientes,
            'devolucionesRecientes' => $devolucionesRecientes,
            'base_efectivo_inicial' => $baseConfigurada,
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Apertura de turno con base inicial (RF-CAJ-02).
     */
    public function apertura(Request $request, AperturaCajaAction $aperturaAction): RedirectResponse
    {
        $validated = $request->validate([
            'caja_id' => ['required', 'exists:cajas,id'],
            'monto_apertura' => ['required', 'numeric', 'min:0'],
        ]);

        $caja = Caja::findOrFail($validated['caja_id']);
        $aperturaAction->handle($caja, (float) $validated['monto_apertura'], $request->user());

        return back()->with('success', "Turno abierto para la caja '{$caja->nombre}' con base de $".number_format($validated['monto_apertura'], 0));
    }

    /**
     * Registra el cobro/pago de un pedido desde caja (RF-CAJ-04, CA-30).
     */
    public function pago(Request $request, Pedido $pedido, RegistrarPagoAction $pagoAction): RedirectResponse
    {
        $validated = $request->validate([
            'propina_valor' => ['nullable', 'numeric', 'min:0'],
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.metodo_pago' => ['required', 'string'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.monto_recibido' => ['nullable', 'numeric', 'min:0'],
            'pagos.*.referencia_transaccion' => ['nullable', 'string', 'max:100'],
            'pagos.*.nota_adicional' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['pagos'] as $idx => $pagoItem) {
            if (($pagoItem['metodo_pago'] ?? 'efectivo') !== 'efectivo' && empty(trim((string) ($pagoItem['referencia_transaccion'] ?? '')))) {
                throw ValidationException::withMessages([
                    "pagos.{$idx}.referencia_transaccion" => ['El número de la transacción es obligatorio para pagos con tarjeta o transferencia.'],
                ]);
            }
        }

        $resultado = $pagoAction->handle(
            $pedido,
            $validated['pagos'],
            isset($validated['propina_valor']) ? (float) $validated['propina_valor'] : null,
            $request->user()
        );

        $cambioStr = $resultado['cambio_total'] > 0
            ? ' | Cambio a devolver: $'.number_format($resultado['cambio_total'], 0)
            : '';

        return back()->with('success', "Pago del Pedido #{$pedido->numero} registrado exitosamente{$cambioStr}.");
    }

    /**
     * Cierre de turno de caja y arqueo (RF-CAJ-05).
     */
    public function cierre(Request $request, SesionCaja $sesionCaja, CierreCajaAction $cierreAction): RedirectResponse
    {
        $validated = $request->validate([
            'monto_cierre_declarado' => ['required', 'numeric', 'min:0'],
            'arqueo_desglose' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $cierreAction->handle(
            $sesionCaja,
            (float) $validated['monto_cierre_declarado'],
            $validated['arqueo_desglose'] ?? null,
            $validated['observaciones'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Sesión de caja cerrada y arqueo completado correctamente.');
    }

    /**
     * Registra una nueva caja física en la sucursal (RF-CAJ-01).
     */
    public function storeCaja(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        Caja::create($validated);

        return back()->with('success', 'Caja creada exitosamente.');
    }

    /**
     * Configura el parámetro de base en efectivo inicial para los turnos de caja (Item 6).
     */
    public function configurarBase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'base_efectivo_inicial' => ['required', 'numeric', 'min:0'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'caja_id' => ['nullable', 'exists:cajas,id'],
            'fecha' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $sucursalId = (int) ($validated['sucursal_id'] ?? session('sucursal_id', 1));
        ConfiguracionService::set('caja.base_efectivo_inicial', (float) $validated['base_efectivo_inicial'], $sucursalId);

        if (! empty($validated['caja_id']) && Schema::hasTable('asignaciones_base_caja')) {
            $caja = Caja::withoutGlobalScopes()->find($validated['caja_id']);
            if ($caja) {
                $fecha = $validated['fecha'] ?? now()->toDateString();
                $existente = AsignacionBaseCaja::withoutGlobalScopes()
                    ->where('sucursal_id', $caja->sucursal_id)
                    ->where('caja_id', $caja->id)
                    ->whereDate('fecha', $fecha)
                    ->first();

                if ($existente) {
                    $existente->update([
                        'monto' => (float) $validated['base_efectivo_inicial'],
                        'user_id' => $caja->user_id,
                        'editado_por_id' => $request->user()?->id,
                        'observaciones' => $validated['observaciones'] ?? $existente->observaciones,
                    ]);
                } else {
                    AsignacionBaseCaja::withoutGlobalScopes()->create([
                        'sucursal_id' => $caja->sucursal_id,
                        'caja_id' => $caja->id,
                        'user_id' => $caja->user_id,
                        'asignado_por_id' => $request->user()?->id,
                        'fecha' => $fecha,
                        'monto' => (float) $validated['base_efectivo_inicial'],
                        'observaciones' => $validated['observaciones'] ?? null,
                    ]);
                }
            }
        }

        return back()->with('success', 'Base inicial en efectivo de caja asignada y registrada correctamente.');
    }

    /**
     * Anula un ítem mal facturado de una comanda antes del cobro, con auditoría (Item 11).
     */
    public function anularItem(Request $request, Pedido $pedido, PedidoItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        if ($pedido->pagado) {
            return back()->with('error', 'No se pueden anular ítems de un pedido que ya fue pagado.');
        }

        DB::transaction(function () use ($pedido, $item, $validated, $request) {
            $item->anulado = true;
            $item->motivo_anulacion = $validated['motivo'];
            $item->anulado_por_id = $request->user()->id;
            $item->save();

            // Revertir inventario si el ítem controlaba inventario
            if ($item->item_menu_id && $item->itemMenu && $item->itemMenu->controla_inventario) {
                $item->itemMenu->increment('existencia', (float) $item->cantidad);
            }

            // Recalcular subtotal, impuestos y total de la comanda
            $itemsActivos = $pedido->items()->where('anulado', false)->get();
            $subtotal = (float) $itemsActivos->sum('subtotal');
            $impuestos = (float) $itemsActivos->sum('impuesto_monto');
            $total = (float) $itemsActivos->sum('total') + (float) $pedido->propina;

            $pedido->update([
                'subtotal' => $subtotal,
                'impuestos' => $impuestos,
                'total' => $total,
            ]);

            activity('caja')
                ->performedOn($item)
                ->causedBy($request->user())
                ->withProperties([
                    'pedido_id' => $pedido->id,
                    'pedido_numero' => $pedido->numero,
                    'item_nombre' => $item->nombre_item,
                    'cantidad' => (float) $item->cantidad,
                    'motivo' => $validated['motivo'],
                ])
                ->log("Ítem '{$item->nombre_item}' anulado del Pedido #{$pedido->numero}. Motivo: {$validated['motivo']}");
        });

        return back()->with('success', "Ítem '{$item->nombre_item}' anulado del pedido correctamente.");
    }
}
