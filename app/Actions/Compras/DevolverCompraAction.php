<?php

namespace App\Actions\Compras;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\DevolucionCompra;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevolverCompraAction
{
    /**
     * Registra la devolución total o parcial de una compra a proveedor (RF-COM-07, Item 4).
     *
     * @param  array<int, array{compra_item_id: int, cantidad: float}>|null  $itemsADevolver
     */
    public function handle(Compra $compra, string $motivo, ?array $itemsADevolver = null, ?User $actor = null): DevolucionCompra
    {
        if ($compra->estado === EstadoCompra::ANULADA || $compra->estado === EstadoCompra::DEVUELTA) {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra ya fue devuelta o anulada previamente.',
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? $compra->user_id);

        return DB::transaction(function () use ($compra, $motivo, $itemsADevolver, $userId) {
            $compra->loadMissing('items');
            $esTotal = empty($itemsADevolver);
            $totalDevuelto = 0.0;
            $itemsDetalle = [];

            if ($esTotal) {
                // Devolución completa de todos los ítems de la orden de compra
                foreach ($compra->items as $item) {
                    $cantidad = (float) $item->cantidad;
                    $itemTotal = (float) $item->total;
                    $totalDevuelto += $itemTotal;

                    $itemsDetalle[] = [
                        'compra_item_id' => $item->id,
                        'descripcion' => $item->descripcion,
                        'cantidad' => $cantidad,
                        'costo_unitario' => (float) $item->costo_unitario,
                        'total' => $itemTotal,
                    ];

                    $this->revertirInventarioItem($compra, $item, $cantidad, $motivo, $userId);
                }

                $compra->estado = EstadoCompra::DEVUELTA;
                $tipo = 'total';
            } else {
                // Devolución parcial de productos específicos
                $totalItemsCompra = $compra->items->count();
                $itemsTotalmenteDevueltos = 0;

                /** @var array<int, array{compra_item_id: int, cantidad: float}> $itemsADevolver */
                foreach ($itemsADevolver as $devItem) {
                    $compraItemId = (int) $devItem['compra_item_id'];
                    $cantidad = (float) $devItem['cantidad'];

                    if ($cantidad <= 0.0) {
                        continue;
                    }

                    /** @var CompraItem|null $item */
                    $item = $compra->items->firstWhere('id', $compraItemId);
                    if (! $item) {
                        continue;
                    }

                    if ($cantidad > (float) $item->cantidad) {
                        throw ValidationException::withMessages([
                            'cantidad' => "La cantidad a devolver de {$item->descripcion} no puede superar {$item->cantidad}.",
                        ]);
                    }

                    $proporcion = $cantidad / (float) $item->cantidad;
                    $subtotalDevuelto = (float) $item->subtotal * $proporcion;
                    $impuestoDevuelto = (float) $item->impuesto_valor * $proporcion;
                    $itemTotalDevuelto = $subtotalDevuelto + $impuestoDevuelto;

                    $totalDevuelto += $itemTotalDevuelto;

                    if ($cantidad >= (float) $item->cantidad) {
                        $itemsTotalmenteDevueltos++;
                    }

                    $itemsDetalle[] = [
                        'compra_item_id' => $item->id,
                        'descripcion' => $item->descripcion,
                        'cantidad' => $cantidad,
                        'costo_unitario' => (float) $item->costo_unitario,
                        'subtotal' => $subtotalDevuelto,
                        'impuesto' => $impuestoDevuelto,
                        'total' => $itemTotalDevuelto,
                    ];

                    $this->revertirInventarioItem($compra, $item, $cantidad, $motivo, $userId);
                }

                $tipo = ($itemsTotalmenteDevueltos === $totalItemsCompra) ? 'total' : 'parcial';
                $compra->estado = ($tipo === 'total') ? EstadoCompra::DEVUELTA : EstadoCompra::DEVUELTA_PARCIAL;
            }

            $compra->save();

            return DevolucionCompra::create([
                'compra_id' => $compra->id,
                'sucursal_id' => $compra->sucursal_id,
                'user_id' => $userId,
                'fecha_devolucion' => now(),
                'motivo' => $motivo,
                'tipo' => $tipo,
                'items_devueltos' => $itemsDetalle,
                'total_devuelto' => $totalDevuelto,
            ]);
        });
    }

    /**
     * Descuenta del inventario y registra el movimiento de kárdex correspondiente.
     */
    protected function revertirInventarioItem(Compra $compra, CompraItem $item, float $cantidad, string $motivo, int $userId): void
    {
        if (! $item->item_menu_id) {
            return;
        }

        /** @var ItemMenu $producto */
        $producto = ItemMenu::withoutGlobalScopes()
            ->lockForUpdate()
            ->findOrFail($item->item_menu_id);

        $cantidadADescontar = $cantidad * (float) $item->factor_conversion;
        $nuevoSaldo = max(0.0, (float) $producto->existencia - $cantidadADescontar);

        $producto->existencia = $nuevoSaldo;
        if ($nuevoSaldo <= 0.0) {
            $producto->disponible = false;
        }
        $producto->save();

        MovimientoInventario::create([
            'sucursal_id' => $compra->sucursal_id,
            'item_menu_id' => $producto->id,
            'tipo' => TipoMovimiento::DEVOLUCION,
            'cantidad' => $cantidadADescontar,
            'costo_unitario' => (float) $item->costo_unitario,
            'costo_total' => $cantidad * (float) $item->costo_unitario,
            'saldo_cantidad' => $nuevoSaldo,
            'saldo_costo' => $nuevoSaldo * (float) $item->costo_unitario,
            'referencia_tipo' => Compra::class,
            'referencia_id' => $compra->id,
            'motivo' => "Devolución de Compra #{$compra->numero_factura}: {$motivo}",
            'user_id' => $userId,
        ]);
    }
}
