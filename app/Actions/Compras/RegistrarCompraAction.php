<?php

namespace App\Actions\Compras;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarCompraAction
{
    /**
     * Registra una compra a proveedor, actualiza existencias e impacta el kárdex (RF-COM-01..08).
     *
     * @param  array{
     *     sucursal_id: int,
     *     proveedor_id: int,
     *     numero_factura: string,
     *     fecha_compra: string,
     *     incluye_impuesto?: bool|null,
     *     impuesto_porcentaje?: float|null,
     *     notas?: string|null,
     *     items: array<int, array{
     *         item_menu_id?: int|null,
     *         descripcion: string,
     *         cantidad: float,
     *         costo_unitario: float,
     *         factor_conversion?: float
     *     }>
     * }  $datos
     */
    public function handle(array $datos, ?User $actor = null): Compra
    {
        if (empty($datos['items'])) {
            throw ValidationException::withMessages([
                'items' => 'La compra debe contener al menos un producto o insumo.',
            ]);
        }

        $userId = $actor ? $actor->id : (auth()->id() ?? 1);
        $incluyeImpuesto = (bool) ($datos['incluye_impuesto'] ?? false);
        $impuestoPorcentaje = (float) ($datos['impuesto_porcentaje'] ?? 0.00);

        return DB::transaction(function () use ($datos, $userId, $incluyeImpuesto, $impuestoPorcentaje) {
            $subtotalAcumulado = 0.0;
            $impuestoAcumulado = 0.0;
            $itemsProcesados = [];

            foreach ($datos['items'] as $itemData) {
                $cantidad = (float) $itemData['cantidad'];
                $costoUnitario = (float) $itemData['costo_unitario'];
                $factorConversion = (float) ($itemData['factor_conversion'] ?? 1.0000);
                $bruto = $cantidad * $costoUnitario;

                if ($incluyeImpuesto && $impuestoPorcentaje > 0.0) {
                    $base = $bruto / (1.0 + ($impuestoPorcentaje / 100.0));
                    $impuesto = $bruto - $base;
                } else {
                    $base = $bruto;
                    $impuesto = $bruto * ($impuestoPorcentaje / 100.0);
                }

                $totalLinea = $base + $impuesto;

                $subtotalAcumulado += $base;
                $impuestoAcumulado += $impuesto;

                $itemsProcesados[] = [
                    'item_menu_id' => $itemData['item_menu_id'] ?? null,
                    'descripcion' => $itemData['descripcion'],
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'factor_conversion' => $factorConversion,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'subtotal' => $base,
                    'impuesto_valor' => $impuesto,
                    'total' => $totalLinea,
                ];
            }

            $totalGeneral = $subtotalAcumulado + $impuestoAcumulado;

            $compra = Compra::create([
                'sucursal_id' => $datos['sucursal_id'],
                'proveedor_id' => $datos['proveedor_id'],
                'user_id' => $userId,
                'numero_factura' => $datos['numero_factura'],
                'fecha_compra' => $datos['fecha_compra'],
                'subtotal' => $subtotalAcumulado,
                'impuesto_total' => $impuestoAcumulado,
                'total' => $totalGeneral,
                'estado' => EstadoCompra::REGISTRADA,
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($itemsProcesados as $linea) {
                $compraItem = CompraItem::create(array_merge($linea, [
                    'compra_id' => $compra->id,
                ]));

                // Si está vinculado a un plato/producto, incrementar existencia e impactar kárdex
                if (! empty($linea['item_menu_id'])) {
                    /** @var ItemMenu $producto */
                    $producto = ItemMenu::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->findOrFail($linea['item_menu_id']);

                    $cantidadAñadida = $linea['cantidad'] * $linea['factor_conversion'];
                    $nuevoSaldo = (float) $producto->existencia + $cantidadAñadida;

                    $producto->existencia = $nuevoSaldo;
                    if ($nuevoSaldo > 0.0 && ! $producto->disponible) {
                        $producto->disponible = true;
                    }
                    $producto->save();

                    MovimientoInventario::create([
                        'sucursal_id' => $compra->sucursal_id,
                        'item_menu_id' => $producto->id,
                        'tipo' => TipoMovimiento::COMPRA,
                        'cantidad' => $cantidadAñadida,
                        'costo_unitario' => $linea['costo_unitario'],
                        'costo_total' => $linea['subtotal'],
                        'saldo_cantidad' => $nuevoSaldo,
                        'saldo_costo' => $nuevoSaldo * $linea['costo_unitario'],
                        'referencia_tipo' => Compra::class,
                        'referencia_id' => $compra->id,
                        'motivo' => "Compra Factura #{$compra->numero_factura}",
                        'user_id' => $userId,
                    ]);
                }
            }

            return $compra->load(['items', 'proveedor']);
        });
    }
}
