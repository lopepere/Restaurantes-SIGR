<?php

namespace App\Actions\Pedidos;

use App\Enums\EstadoPedido;
use App\Events\CancelacionSolicitada;
use App\Events\PedidoActualizado;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\SolicitudCancelacion;
use App\Services\ConfiguracionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitarCancelacionAction
{
    public function __construct(
        protected ActualizarEstadoPedidoAction $actualizarEstadoPedidoAction
    ) {}

    /**
     * Procesa la solicitud de cancelación de un pedido (CA-08, RF-PED-09).
     */
    public function handle(
        Pedido $pedido,
        string $motivo,
        ?int $solicitanteId = null,
        ?string $solicitanteNombre = null,
        ?string $solicitanteTelefono = null
    ): Pedido {
        if (! $pedido->estaActivo()) {
            throw ValidationException::withMessages([
                'pedido' => 'No es posible solicitar la cancelación de un pedido finalizado o ya cancelado.',
            ]);
        }

        if ($pedido->estado === EstadoPedido::CANCELACION_SOLICITADA) {
            throw ValidationException::withMessages([
                'pedido' => 'Ya existe una solicitud de cancelación en trámite para este pedido.',
            ]);
        }

        // Evaluar parámetro opcional de cancelación automática en PENDIENTE
        $cancelacionAutomatica = (bool) ConfiguracionService::obtener('pedidos.cancelacion_automatica', $pedido->sucursal_id, false);

        if ($cancelacionAutomatica && $pedido->estado === EstadoPedido::PENDIENTE) {
            return $this->actualizarEstadoPedidoAction->handle(
                $pedido,
                EstadoPedido::CANCELADO,
                "Cancelación automática aprobada por sistema: {$motivo}",
                $solicitanteId
            );
        }

        return DB::transaction(function () use ($pedido, $motivo, $solicitanteId, $solicitanteNombre, $solicitanteTelefono) {
            $estadoAnterior = $pedido->estado;

            $pedido->solicito_cancelacion = true;
            $pedido->motivo_cancelacion = $motivo;
            $pedido->cancelacion_solicitada_at = now();
            $pedido->estado = EstadoPedido::CANCELACION_SOLICITADA;
            $pedido->save();

            // Crear registro formal de solicitud
            SolicitudCancelacion::create([
                'pedido_id' => $pedido->id,
                'solicitado_por_id' => $solicitanteId ?? auth()->id(),
                'motivo' => $motivo,
                'estado' => 'pendiente',
                'solicitada_at' => now(),
            ]);

            // Historial de estados
            PedidoEstado::create([
                'pedido_id' => $pedido->id,
                'user_id' => $solicitanteId ?? auth()->id(),
                'estado_anterior' => $estadoAnterior->value,
                'estado_nuevo' => EstadoPedido::CANCELACION_SOLICITADA->value,
                'motivo' => "Solicitud de cancelación: {$motivo}",
                'created_at' => now(),
            ]);

            // Difusión en tiempo real con alerta sonora/visual para administración (RF-PED-09, CA-08)
            event(new CancelacionSolicitada(
                $pedido,
                $motivo,
                $solicitanteNombre,
                $solicitanteTelefono
            ));

            event(new PedidoActualizado($pedido, "Solicitud de cancelación en revisión: {$motivo}"));

            return $pedido;
        });
    }
}
