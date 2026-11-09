<?php

namespace App\Actions\Compras;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\DevolucionCompra;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevolverCompraAction
{
    /**
     * Registra la devolución formal de una compra a proveedor (RF-COM-07).
     */
    public function handle(Compra $compra, string $motivo, ?User $actor = null): DevolucionCompra
    {
        if ($compra->estado !== EstadoCompra::REGISTRADA) {
            throw ValidationException::withMessages([
                'compra' => 'Solo se pueden devolver compras en estado Registrada.',
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? $compra->user_id);

        return DB::transaction(function () use ($compra, $motivo, $userId) {
            // Revertir inventario de los items vinculados
            foreach ($compra->items as $item) {
                if ($item->item_menu_id) {
                    /** @var ItemMenu $producto */
                    $producto = ItemMenu::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->findOrFail($item->item_menu_id);

                    $cantidadADevolver = (float) $item->cantidad * (float) $item->factor_conversion;
                    $nuevoSaldo = max(0.0, (float) $producto->existencia - $cantidadADevolver);

                    $producto->existencia = $nuevoSaldo;
                    if ($nuevoSaldo <= 0.0) {
                        $producto->disponible = false;
                    }
                    $producto->save();

                    MovimientoInventario::create([
                        'sucursal_id' => $compra->sucursal_id,
                        'item_menu_id' => $producto->id,
                        'tipo' => TipoMovimiento::DEVOLUCION,
                        'cantidad' => $cantidadADevolver,
                        'costo_unitario' => (float) $item->costo_unitario,
                        'costo_total' => (float) $item->subtotal,
                        'saldo_cantidad' => $nuevoSaldo,
                        'saldo_costo' => $nuevoSaldo * (float) $item->costo_unitario,
                        'referencia_tipo' => Compra::class,
                        'referencia_id' => $compra->id,
                        'motivo' => "Devolución de Compra #{$compra->numero_factura}: {$motivo}",
                        'user_id' => $userId,
                    ]);
                }
            }

            $compra->estado = EstadoCompra::DEVUELTA;
            $compra->save();

            return DevolucionCompra::create([
                'compra_id' => $compra->id,
                'sucursal_id' => $compra->sucursal_id,
                'user_id' => $userId,
                'fecha_devolucion' => now(),
                'motivo' => $motivo,
                'total_devuelto' => $compra->total,
            ]);
        });
    }
}
