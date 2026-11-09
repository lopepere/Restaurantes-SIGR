<?php

namespace App\Actions\Pedidos;

use App\Models\Pedido;

class RepetirPedidoAction
{
    /**
     * Evalúa los ítems de un pedido anterior frente al catálogo actual,
     * devolviendo los productos vigentes para armar el carrito y advirtiendo sobre agotados (RF-PED-18).
     *
     * @return array{
     *     pedido_original_numero: string,
     *     sucursal_id: int,
     *     items_disponibles: array<int, array{item_id: int, nombre: string, cantidad: float, precio_vigente: float, notas: ?string}>,
     *     items_no_disponibles: array<int, array{item_id: int|null, nombre: string, motivo: string}>,
     *     puede_repetir_completo: bool
     * }
     */
    public function handle(Pedido $pedido): array
    {
        $pedido->load(['items.itemMenu']);

        $disponibles = [];
        $noDisponibles = [];

        foreach ($pedido->items as $pedidoItem) {
            $item = $pedidoItem->itemMenu;

            if (! $item || ! $item->activo) {
                $noDisponibles[] = [
                    'item_id' => $pedidoItem->item_menu_id,
                    'nombre' => $pedidoItem->nombre_item,
                    'motivo' => 'Producto retirado o ya no está disponible en la carta',
                ];

                continue;
            }

            if (! $item->disponible) {
                $noDisponibles[] = [
                    'item_id' => $item->id,
                    'nombre' => $item->nombre,
                    'motivo' => 'Temporalmente no disponible',
                ];

                continue;
            }

            $cantidad = (float) $pedidoItem->cantidad;

            if ($item->controla_inventario && (float) $item->existencia < $cantidad) {
                $noDisponibles[] = [
                    'item_id' => $item->id,
                    'nombre' => $item->nombre,
                    'motivo' => 'Existencias insuficientes para la cantidad solicitada',
                ];

                continue;
            }

            $disponibles[] = [
                'item_id' => $item->id,
                'nombre' => $item->nombre,
                'cantidad' => $cantidad,
                'precio_vigente' => (float) $item->precio,
                'notas' => $pedidoItem->notas,
            ];
        }

        return [
            'pedido_original_numero' => $pedido->numero,
            'sucursal_id' => $pedido->sucursal_id,
            'items_disponibles' => $disponibles,
            'items_no_disponibles' => $noDisponibles,
            'puede_repetir_completo' => empty($noDisponibles),
        ];
    }
}
