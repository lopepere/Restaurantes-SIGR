<?php

namespace App\Observers;

use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Models\Espacio;
use App\Models\Pedido;

class PedidoObserver
{
    /**
     * Handle the Pedido "saved" event.
     */
    public function saved(Pedido $pedido): void
    {
        // 1. Si cambió de mesa, evaluar la mesa anterior
        $mesaAnteriorId = $pedido->getOriginal('espacio_id');
        if ($mesaAnteriorId && $mesaAnteriorId !== $pedido->espacio_id) {
            $this->actualizarEstadoMesa((int) $mesaAnteriorId);
        }

        // 2. Evaluar la mesa actual
        if ($pedido->espacio_id) {
            $this->actualizarEstadoMesa((int) $pedido->espacio_id);
        }
    }

    /**
     * Handle the Pedido "deleted" event.
     */
    public function deleted(Pedido $pedido): void
    {
        if ($pedido->espacio_id) {
            $this->actualizarEstadoMesa((int) $pedido->espacio_id);
        }
    }

    /**
     * Actualiza el estado de la mesa (OCUPADO si tiene pedidos activos, LIBRE si no).
     */
    protected function actualizarEstadoMesa(int $espacioId): void
    {
        $espacio = Espacio::find($espacioId);
        if (! $espacio) {
            return;
        }

        $hayPedidosActivos = Pedido::where('espacio_id', $espacioId)
            ->whereNotIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO])
            ->exists();

        $nuevoEstado = $hayPedidosActivos ? EstadoEspacio::OCUPADO : EstadoEspacio::LIBRE;

        if ($espacio->estado !== $nuevoEstado) {
            $espacio->update(['estado' => $nuevoEstado]);
        }
    }
}
