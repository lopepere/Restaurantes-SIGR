<?php

namespace App\Actions\Caja;

use App\Actions\Comprobantes\GenerarComprobanteAction;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Enums\MetodoPago;
use App\Models\Comprobante;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarPagoAction
{
    public function __construct(
        protected GenerarComprobanteAction $generarComprobanteAction
    ) {}

    /**
     * Registra uno o varios pagos para un pedido (pagos mixtos, billete recibido, devuelta y propina opcional) (RF-CAJ-04, CA-30).
     *
     * @param  array<int, array{
     *     metodo_pago: string,
     *     monto: float,
     *     monto_recibido?: float|null,
     *     referencia_transaccion?: string|null,
     *     nota_adicional?: string|null
     * }>  $pagosData
     * @return array{
     *     pedido: Pedido,
     *     pagos: array<int, Pago>,
     *     comprobante: Comprobante|null,
     *     cambio_total: float,
     *     total_cubierto: bool
     * }
     */
    public function handle(
        Pedido $pedido,
        array $pagosData,
        ?float $propinaValor = null,
        ?User $actor = null
    ): array {
        if ($pedido->estado === EstadoPedido::CANCELADO) {
            throw ValidationException::withMessages([
                'pedido' => 'No es posible registrar pagos en un pedido cancelado.',
            ]);
        }

        if ($pedido->pagado) {
            throw ValidationException::withMessages([
                'pedido' => 'Este pedido ya se encuentra totalmente pagado.',
            ]);
        }

        if (empty($pagosData)) {
            throw ValidationException::withMessages([
                'pagos' => 'Debe registrar al menos un método de pago.',
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? 1);

        return DB::transaction(function () use ($pedido, $pagosData, $propinaValor, $userId, $actor) {
            // Actualizar propina voluntaria si fue proporcionada (SC-02)
            if ($propinaValor !== null && $propinaValor >= 0) {
                $pedido->propina = $propinaValor;
                $pedido->total = (float) $pedido->subtotal - (float) $pedido->descuento + (float) $pedido->impuestos + $propinaValor;
                $pedido->save();
            }

            // Buscar o asociar sesión de caja abierta de la sucursal
            $sesionCaja = SesionCaja::where('sucursal_id', $pedido->sucursal_id)
                ->where('estado', EstadoSesionCaja::ABIERTA)
                ->orderByDesc('id')
                ->first();

            $pagosCreados = [];
            $cambioTotal = 0.0;
            $metodoPrincipal = null;

            foreach ($pagosData as $pData) {
                $monto = (float) $pData['monto'];
                $montoRecibido = isset($pData['monto_recibido']) ? (float) $pData['monto_recibido'] : $monto;
                $cambio = max(0.0, $montoRecibido - $monto);
                $cambioTotal += $cambio;

                $metodoEnum = MetodoPago::tryFrom($pData['metodo_pago']) ?? MetodoPago::EFECTIVO;
                if (! $metodoPrincipal) {
                    $metodoPrincipal = $metodoEnum->value;
                }

                $pago = Pago::create([
                    'pedido_id' => $pedido->id,
                    'sesion_caja_id' => $sesionCaja?->id,
                    'user_id' => $userId,
                    'monto' => $monto,
                    'propina_valor' => 0.00, // La propina total se consolida en el pedido
                    'metodo_pago' => $metodoEnum,
                    'monto_recibido' => $montoRecibido,
                    'cambio_devuelto' => $cambio,
                    'referencia_transaccion' => $pData['referencia_transaccion'] ?? null,
                    'nota_adicional' => $pData['nota_adicional'] ?? null,
                ]);

                $pagosCreados[] = $pago;
            }

            // Validar si el acumulado de pagos cubre el total del pedido
            $totalPagado = (float) Pago::where('pedido_id', $pedido->id)->sum('monto');
            $totalCubierto = $totalPagado >= ((float) $pedido->total - 0.01); // Margen de redondeo

            $comprobante = null;

            if ($totalCubierto) {
                $pedido->pagado = true;
                $pedido->estado = EstadoPedido::PAGADO;
                $pedido->save();

                // Si fue pago mixto, registrar como tal
                if (count($pagosData) > 1) {
                    $metodoPrincipal = 'mixto';
                }

                // Generación automática del Comprobante de Venta FAC inmutable (SC-03)
                $comprobante = $this->generarComprobanteAction->handle($pedido, $metodoPrincipal, $actor);
            }

            return [
                'pedido' => $pedido->fresh(['items', 'pagos', 'comprobante']),
                'pagos' => $pagosCreados,
                'comprobante' => $comprobante,
                'cambio_total' => $cambioTotal,
                'total_cubierto' => $totalCubierto,
            ];
        });
    }
}
