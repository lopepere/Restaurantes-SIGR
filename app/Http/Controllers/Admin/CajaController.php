<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Caja\CierreCajaAction;
use App\Actions\Caja\RegistrarPagoAction;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', $primerId));

        $cajas = Caja::where('sucursal_id', $sucursalId)->where('activa', true)->get();

        // Sesión activa actual en la sucursal
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

        // Pedidos pendientes de pago en la sucursal
        $pedidosPorCobrar = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('pagado', false)
            ->where('estado', '!=', EstadoPedido::CANCELADO)
            ->with(['espacio'])
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

        return Inertia::render('Admin/Caja/Index', [
            'cajas' => $cajas,
            'sesionActiva' => $resumenSesion,
            'pagosSesion' => $pagosSesion,
            'pedidosPorCobrar' => $pedidosPorCobrar,
            'cierresRecientes' => $cierresRecientes,
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
        ]);

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
}
