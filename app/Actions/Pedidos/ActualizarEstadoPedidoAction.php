<?php

namespace App\Actions\Pedidos;

use App\Enums\EstadoPedido;
use App\Events\PedidoActualizado;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Notifications\EstadoPedidoActualizadoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarEstadoPedidoAction
{
    /**
     * Transiciona el estado de un pedido según la máquina de estados de la ERS 8.3,
     * reponiendo inventario en caso de cancelación y notificando en tiempo real.
     */
    public function handle(Pedido $pedido, EstadoPedido $nuevoEstado, ?string $motivo = null, ?int $userId = null): Pedido
    {
        $estadoAnterior = $pedido->estado;

        if ($estadoAnterior === $nuevoEstado) {
            return $pedido;
        }

        if (! $estadoAnterior->puedeTransicionarHacia($nuevoEstado)) {
            throw ValidationException::withMessages([
                'estado' => "Transición no permitida de {$estadoAnterior->value} a {$nuevoEstado->value} según las reglas del sistema.",
            ]);
        }

        return DB::transaction(function () use ($pedido, $estadoAnterior, $nuevoEstado, $motivo, $userId) {
            // Reposición de inventario si el pedido se cancela (CA-27, RF-INV-02)
            if ($nuevoEstado === EstadoPedido::CANCELADO) {
                $pedido->loadMissing(['items.itemMenu.comboItems.item']);

                foreach ($pedido->items as $pedidoItem) {
                    $item = $pedidoItem->itemMenu;
                    if (! $item) {
                        continue;
                    }

                    $cantidad = (float) $pedidoItem->cantidad;

                    if ($item->es_combo) {
                        foreach ($item->comboItems as $ci) {
                            $compItem = $ci->item;
                            if ($compItem && $compItem->controla_inventario) {
                                /** @var ItemMenu|null $compLocked */
                                $compLocked = ItemMenu::withoutGlobalScopes()
                                    ->lockForUpdate()
                                    ->find($compItem->id);

                                if ($compLocked) {
                                    $compLocked->existencia = (float) $compLocked->existencia + ((float) $ci->cantidad * $cantidad);
                                    $compLocked->save();
                                }
                            }
                        }
                    } elseif ($item->controla_inventario) {
                        /** @var ItemMenu|null $itemLocked */
                        $itemLocked = ItemMenu::withoutGlobalScopes()
                            ->lockForUpdate()
                            ->find($item->id);

                        if ($itemLocked) {
                            $itemLocked->existencia = (float) $itemLocked->existencia + $cantidad;
                            $itemLocked->save();
                        }
                    }
                }

                $pedido->solicito_cancelacion = false;
                if ($userId) {
                    $pedido->cancelado_por_id = $userId;
                }
            }

            $pedido->estado = $nuevoEstado;

            if ($nuevoEstado === EstadoPedido::PAGADO) {
                $pedido->pagado = true;
            }

            $pedido->save();

            // Registro en historial
            PedidoEstado::create([
                'pedido_id' => $pedido->id,
                'user_id' => $userId ?? auth()->id(),
                'estado_anterior' => $estadoAnterior->value,
                'estado_nuevo' => $nuevoEstado->value,
                'motivo' => $motivo ?: "Cambio a {$nuevoEstado->etiqueta()}",
                'created_at' => now(),
            ]);

            // Difusión Reverb
            event(new PedidoActualizado($pedido, $motivo));

            // Notificación por correo
            if ($pedido->cliente?->email) {
                $pedido->cliente->notify(new EstadoPedidoActualizadoNotification($pedido, $motivo));
            }

            return $pedido;
        });
    }
}
