<?php

namespace App\Actions\Pedidos;

use App\Events\PedidoActualizado;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use App\Services\MonedaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditarPedidoAction
{
    /**
     * Aplica modificaciones a un pedido respetando la ventana de edición y permisos por rol (CA-10).
     *
     * @param  array{notas?: string|null, items?: array<int, array{item_menu_id: int, cantidad: float|int, notas?: string|null}>|null}  $cambios
     */
    public function handle(Pedido $pedido, User $actor, array $cambios): Pedido
    {
        if (! $pedido->estaActivo()) {
            throw ValidationException::withMessages([
                'pedido' => 'No es posible modificar un pedido que ya ha sido pagado o cancelado.',
            ]);
        }

        $esAdmin = $actor->hasAnyRole(['admin', 'super_admin']);
        $esMesero = $actor->hasRole('mesero');
        $esCliente = $actor->hasRole('cliente');

        // Clientes: solo pueden editar notas dentro de la ventana y en estado PENDIENTE
        if ($esCliente) {
            if (isset($cambios['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Los clientes solo pueden agregar o modificar notas dentro de la ventana de 5 minutos, no los productos.',
                ]);
            }

            if (! $pedido->puedeEditarNotas()) {
                throw ValidationException::withMessages([
                    'notas' => 'La ventana de edición de 5 minutos para este pedido ha expirado.',
                ]);
            }

            $pedido->notas = $cambios['notas'] ?? $pedido->notas;
            $pedido->save();

            event(new PedidoActualizado($pedido, 'Notas del cliente actualizadas'));

            return $pedido;
        }

        // Meseros: solo sus pedidos en PENDIENTE dentro de la ventana
        if ($esMesero) {
            if ($pedido->mesero_id !== $actor->id) {
                throw ValidationException::withMessages([
                    'pedido' => 'Solo puedes modificar los pedidos que tú mismo hayas tomado.',
                ]);
            }

            if (! $pedido->puedeEditarItems()) {
                throw ValidationException::withMessages([
                    'pedido' => 'La ventana de edición de 5 minutos ha expirado o el pedido ya no está en estado pendiente.',
                ]);
            }
        }

        // Aplicación de cambios (mesero dentro de ventana o admin)
        return DB::transaction(function () use ($pedido, $cambios) {
            if (isset($cambios['notas'])) {
                $pedido->notas = $cambios['notas'];
            }

            if (isset($cambios['items'])) {
                // Revertir inventario de ítems anteriores
                $pedido->loadMissing(['items.itemMenu']);
                foreach ($pedido->items as $pi) {
                    $item = $pi->itemMenu;
                    if ($item && $item->controla_inventario && ! $item->es_combo) {
                        $locked = ItemMenu::withoutGlobalScopes()->lockForUpdate()->find($item->id);
                        if ($locked) {
                            $locked->existencia = (float) $locked->existencia + (float) $pi->cantidad;
                            $locked->save();
                        }
                    }
                }

                // Borrar ítems actuales y recrear con los nuevos
                $pedido->items()->delete();

                $subtotalAcumulado = 0.00;
                $descuentoAcumulado = 0.00;
                $impuestosAcumulados = 0.00;
                $baseSinImpuestos = 0.00;

                foreach ($cambios['items'] as $itemData) {
                    $itemId = (int) $itemData['item_menu_id'];
                    $cantidad = max(1.0, (float) $itemData['cantidad']);

                    /** @var ItemMenu $item */
                    $item = ItemMenu::withoutGlobalScopes()->findOrFail($itemId);

                    if ($item->controla_inventario && ! $item->es_combo) {
                        $locked = ItemMenu::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->id);
                        if ((float) $locked->existencia < $cantidad) {
                            throw ValidationException::withMessages([
                                'items' => "Existencias insuficientes para el producto {$item->nombre}.",
                            ]);
                        }
                        $locked->existencia = (float) $locked->existencia - $cantidad;
                        $locked->save();
                    }

                    $precioOriginal = (float) $item->precio;
                    $desgloseImpuesto = MonedaService::desglosarImpuestoIncluido($precioOriginal, $item->impuesto_porcentaje);
                    $subtotalLinea = MonedaService::redondear($precioOriginal * $cantidad);
                    $impuestoLinea = MonedaService::redondear($desgloseImpuesto['impuesto'] * $cantidad);
                    $baseSinImpuestoLinea = MonedaService::redondear($desgloseImpuesto['base'] * $cantidad);

                    $subtotalAcumulado += $subtotalLinea;
                    $impuestosAcumulados += $impuestoLinea;
                    $baseSinImpuestos += $baseSinImpuestoLinea;

                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'item_menu_id' => $item->id,
                        'nombre_item' => $item->nombre,
                        'precio_unitario' => $precioOriginal,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotalLinea,
                        'impuesto_tipo' => $item->impuesto_tipo->value,
                        'impuesto_porcentaje' => (float) $item->impuesto_porcentaje,
                        'impuesto_monto' => $impuestoLinea,
                        'descuento_aplicado' => 0.00,
                        'total' => $subtotalLinea,
                        'notas' => $itemData['notas'] ?? null,
                    ]);
                }

                $propinaValor = 0.00;
                if ($pedido->propina_porcentaje && (float) $pedido->propina_porcentaje > 0) {
                    $propinaValor = MonedaService::calcularPropina($baseSinImpuestos, (float) $pedido->propina_porcentaje);
                } elseif ((float) $pedido->propina > 0) {
                    $propinaValor = (float) $pedido->propina;
                }

                $pedido->subtotal = MonedaService::redondear($subtotalAcumulado);
                $pedido->descuento = MonedaService::redondear($descuentoAcumulado);
                $pedido->impuestos = MonedaService::redondear($impuestosAcumulados);
                $pedido->propina = $propinaValor;
                $pedido->total = MonedaService::redondear($subtotalAcumulado - $descuentoAcumulado + $propinaValor);
            }

            $pedido->save();

            event(new PedidoActualizado($pedido, 'Pedido modificado exitosamente'));

            return $pedido;
        });
    }
}
