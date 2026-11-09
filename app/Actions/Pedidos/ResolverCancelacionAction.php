<?php

namespace App\Actions\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\SolicitudCancelacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolverCancelacionAction
{
    public function __construct(
        protected ActualizarEstadoPedidoAction $actualizarEstadoPedidoAction
    ) {}

    /**
     * Resuelve una solicitud de cancelación (CA-09).
     */
    public function handle(Pedido $pedido, bool $aprobar, ?string $respuestaAdmin = null, ?User $admin = null): Pedido
    {
        if ($pedido->estado !== EstadoPedido::CANCELACION_SOLICITADA) {
            throw ValidationException::withMessages([
                'pedido' => 'El pedido no tiene una solicitud de cancelación pendiente de resolución.',
            ]);
        }

        $adminUser = $admin ?? auth()->user();
        $adminId = $adminUser ? $adminUser->id : null;
        $adminNombre = $adminUser ? $adminUser->nombre : 'Administración';

        return DB::transaction(function () use ($pedido, $aprobar, $respuestaAdmin, $adminId, $adminNombre) {
            $solicitud = SolicitudCancelacion::where('pedido_id', $pedido->id)
                ->where('estado', 'pendiente')
                ->latest('id')
                ->first();

            if ($aprobar) {
                $pedido = $this->actualizarEstadoPedidoAction->handle(
                    $pedido,
                    EstadoPedido::CANCELADO,
                    "Cancelación aprobada por {$adminNombre}. Motivo: {$respuestaAdmin}",
                    $adminId
                );

                if ($solicitud) {
                    $solicitud->update([
                        'estado' => 'aprobada',
                        'resuelto_por_id' => $adminId,
                        'respuesta_admin' => $respuestaAdmin,
                        'resuelta_at' => now(),
                    ]);
                }
            } else {
                // Recuperar estado previo antes de la solicitud de cancelación
                $estadoPrevioStr = PedidoEstado::where('pedido_id', $pedido->id)
                    ->where('estado_nuevo', '!=', EstadoPedido::CANCELACION_SOLICITADA->value)
                    ->latest('id')
                    ->value('estado_nuevo') ?? EstadoPedido::EN_PREPARACION->value;

                $estadoPrevio = EstadoPedido::tryFrom($estadoPrevioStr) ?? EstadoPedido::EN_PREPARACION;

                $pedido = $this->actualizarEstadoPedidoAction->handle(
                    $pedido,
                    $estadoPrevio,
                    "Cancelación rechazada por {$adminNombre}. Motivo: {$respuestaAdmin}",
                    $adminId
                );

                if ($solicitud) {
                    $solicitud->update([
                        'estado' => 'rechazada',
                        'resuelto_por_id' => $adminId,
                        'respuesta_admin' => $respuestaAdmin,
                        'resuelta_at' => now(),
                    ]);
                }
            }

            $pedido->solicito_cancelacion = false;
            $pedido->save();

            return $pedido;
        });
    }
}
