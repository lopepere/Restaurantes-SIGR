<?php

namespace App\Actions\Compras;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnularCompraAction
{
    /**
     * Anula una compra registrada descontando las existencias previamente sumadas (RF-COM-06).
     * Nota: No se permite borrado ni edición de compras; únicamente anulación documentada.
     */
    public function handle(Compra $compra, string $motivo, ?User $actor = null): Compra
    {
        if ($compra->estado !== EstadoCompra::REGISTRADA) {
            throw ValidationException::withMessages([
                'compra' => 'Solo se pueden anular compras en estado Registrada.',
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? $compra->user_id);

        return DB::transaction(function () use ($compra, $motivo, $userId) {
            // Revertir inventario de cada item
            foreach ($compra->items as $item) {
                if ($item->item_menu_id) {
                    /** @var ItemMenu $producto */
                    $producto = ItemMenu::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->findOrFail($item->item_menu_id);

                    $cantidadADescontar = (float) $item->cantidad * (float) $item->factor_conversion;
                    $nuevoSaldo = max(0.0, (float) $producto->existencia - $cantidadADescontar);

                    $producto->existencia = $nuevoSaldo;
                    if ($nuevoSaldo <= 0.0) {
                        $producto->disponible = false;
                    }
                    $producto->save();

                    MovimientoInventario::create([
                        'sucursal_id' => $compra->sucursal_id,
                        'item_menu_id' => $producto->id,
                        'tipo' => TipoMovimiento::ANULACION,
                        'cantidad' => $cantidadADescontar,
                        'costo_unitario' => (float) $item->costo_unitario,
                        'costo_total' => (float) $item->subtotal,
                        'saldo_cantidad' => $nuevoSaldo,
                        'saldo_costo' => $nuevoSaldo * (float) $item->costo_unitario,
                        'referencia_tipo' => Compra::class,
                        'referencia_id' => $compra->id,
                        'motivo' => "Anulación de Compra #{$compra->numero_factura}: {$motivo}",
                        'user_id' => $userId,
                    ]);
                }
            }

            $compra->estado = EstadoCompra::ANULADA;
            $compra->motivo_anulacion = $motivo;
            $compra->anulado_por_id = $userId;
            $compra->anulado_at = now();
            $compra->save();

            return $compra;
        });
    }
}
