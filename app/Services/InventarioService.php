<?php

namespace App\Services;

use App\Enums\ModoInventario;
use App\Enums\TipoMovimiento;
use App\Events\ProductoAgotado;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    /**
     * Descuenta existencias de un ítem de menú registrando el movimiento de salida en el kárdex (CA-27).
     */
    public static function descontar(
        ItemMenu $item,
        float $cantidad,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $motivo = null,
        ?User $actor = null
    ): MovimientoInventario {
        return DB::transaction(function () use ($item, $cantidad, $referenciaTipo, $referenciaId, $motivo, $actor) {
            /** @var ItemMenu $itemBloqueado */
            $itemBloqueado = ItemMenu::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $saldoAnterior = (float) $itemBloqueado->existencia;
            $nuevoSaldo = max(0.0, $saldoAnterior - $cantidad);

            $itemBloqueado->existencia = $nuevoSaldo;
            if ($nuevoSaldo <= 0.0) {
                $itemBloqueado->disponible = false;
            }
            $itemBloqueado->save();

            $movimiento = MovimientoInventario::create([
                'sucursal_id' => $itemBloqueado->sucursal_id,
                'item_menu_id' => $itemBloqueado->id,
                'tipo' => TipoMovimiento::VENTA,
                'cantidad' => $cantidad,
                'costo_unitario' => (float) $itemBloqueado->precio,
                'costo_total' => $cantidad * (float) $itemBloqueado->precio,
                'saldo_cantidad' => $nuevoSaldo,
                'saldo_costo' => $nuevoSaldo * (float) $itemBloqueado->precio,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $referenciaId,
                'motivo' => $motivo ?? 'Salida por venta de pedido',
                'user_id' => $actor ? $actor->id : auth()->id(),
            ]);

            if ($nuevoSaldo <= 0.0) {
                event(new ProductoAgotado($itemBloqueado->sucursal_id, $itemBloqueado->id, $itemBloqueado->nombre));
            }

            return $movimiento;
        });
    }

    /**
     * Repone existencias por cancelación o devolución de pedido (CA-27).
     */
    public static function reponer(
        ItemMenu $item,
        float $cantidad,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $motivo = null,
        ?User $actor = null
    ): MovimientoInventario {
        return DB::transaction(function () use ($item, $cantidad, $referenciaTipo, $referenciaId, $motivo, $actor) {
            /** @var ItemMenu $itemBloqueado */
            $itemBloqueado = ItemMenu::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $saldoAnterior = (float) $itemBloqueado->existencia;
            $nuevoSaldo = $saldoAnterior + $cantidad;

            $itemBloqueado->existencia = $nuevoSaldo;
            if ($nuevoSaldo > 0.0 && ! $itemBloqueado->disponible) {
                $itemBloqueado->disponible = true;
            }
            $itemBloqueado->save();

            return MovimientoInventario::create([
                'sucursal_id' => $itemBloqueado->sucursal_id,
                'item_menu_id' => $itemBloqueado->id,
                'tipo' => TipoMovimiento::CANCELACION,
                'cantidad' => $cantidad,
                'costo_unitario' => (float) $itemBloqueado->precio,
                'costo_total' => $cantidad * (float) $itemBloqueado->precio,
                'saldo_cantidad' => $nuevoSaldo,
                'saldo_costo' => $nuevoSaldo * (float) $itemBloqueado->precio,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $referenciaId,
                'motivo' => $motivo ?? 'Reposición por cancelación de pedido',
                'user_id' => $actor ? $actor->id : auth()->id(),
            ]);
        });
    }

    /**
     * Registra el inventario inicial único de un ítem de menú (RF-INV-04).
     */
    public static function registrarInventarioInicial(
        ItemMenu $item,
        float $cantidad,
        float $costoUnitario,
        ?User $actor = null
    ): MovimientoInventario {
        return DB::transaction(function () use ($item, $cantidad, $costoUnitario, $actor) {
            /** @var ItemMenu $itemBloqueado */
            $itemBloqueado = ItemMenu::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $existeInicial = MovimientoInventario::withoutGlobalScopes()
                ->where('item_menu_id', $itemBloqueado->id)
                ->where('tipo', TipoMovimiento::INICIAL)
                ->exists();

            if ($existeInicial) {
                throw ValidationException::withMessages([
                    'item' => "El ítem '{$itemBloqueado->nombre}' ya tiene un inventario inicial registrado.",
                ]);
            }

            $itemBloqueado->existencia = $cantidad;
            $itemBloqueado->disponible = $cantidad > 0;
            $itemBloqueado->save();

            return MovimientoInventario::create([
                'sucursal_id' => $itemBloqueado->sucursal_id,
                'item_menu_id' => $itemBloqueado->id,
                'tipo' => TipoMovimiento::INICIAL,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $cantidad * $costoUnitario,
                'saldo_cantidad' => $cantidad,
                'saldo_costo' => $cantidad * $costoUnitario,
                'referencia_tipo' => null,
                'referencia_id' => null,
                'motivo' => 'Inventario inicial de apertura',
                'user_id' => $actor ? $actor->id : auth()->id(),
            ]);
        });
    }

    /**
     * Ajusta manualmente las existencias con justificación obligatoria (RF-INV-05).
     */
    public static function ajustar(
        ItemMenu $item,
        float $nuevaExistencia,
        string $motivo,
        ?User $actor = null
    ): MovimientoInventario {
        return DB::transaction(function () use ($item, $nuevaExistencia, $motivo, $actor) {
            /** @var ItemMenu $itemBloqueado */
            $itemBloqueado = ItemMenu::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $saldoAnterior = (float) $itemBloqueado->existencia;
            $diferencia = $nuevaExistencia - $saldoAnterior;

            if ($diferencia === 0.0) {
                throw ValidationException::withMessages([
                    'existencia' => 'La nueva existencia es idéntica al saldo actual.',
                ]);
            }

            $tipo = $diferencia > 0 ? TipoMovimiento::AJUSTE_POSITIVO : TipoMovimiento::AJUSTE_NEGATIVO;
            $cantidadMov = abs($diferencia);

            $itemBloqueado->existencia = $nuevaExistencia;
            $itemBloqueado->disponible = $nuevaExistencia > 0;
            $itemBloqueado->save();

            return MovimientoInventario::create([
                'sucursal_id' => $itemBloqueado->sucursal_id,
                'item_menu_id' => $itemBloqueado->id,
                'tipo' => $tipo,
                'cantidad' => $cantidadMov,
                'costo_unitario' => (float) $itemBloqueado->precio,
                'costo_total' => $cantidadMov * (float) $itemBloqueado->precio,
                'saldo_cantidad' => $nuevaExistencia,
                'saldo_costo' => $nuevaExistencia * (float) $itemBloqueado->precio,
                'referencia_tipo' => null,
                'referencia_id' => null,
                'motivo' => $motivo,
                'user_id' => $actor ? $actor->id : auth()->id(),
            ]);
        });
    }

    /**
     * Ajuste por lote para múltiples productos en una sola transacción (RF-INV-06).
     *
     * @param  array<int, array{item_id: int, nueva_existencia: float}>  $itemsAjuste
     * @return array<int, MovimientoInventario>
     */
    public static function actualizarPorLote(
        int $sucursalId,
        array $itemsAjuste,
        string $motivo,
        ?User $actor = null
    ): array {
        return DB::transaction(function () use ($sucursalId, $itemsAjuste, $motivo, $actor) {
            $movimientos = [];
            foreach ($itemsAjuste as $ajuste) {
                $item = ItemMenu::withoutGlobalScopes()
                    ->where('sucursal_id', $sucursalId)
                    ->findOrFail($ajuste['item_id']);

                if ((float) $item->existencia !== (float) $ajuste['nueva_existencia']) {
                    $movimientos[] = self::ajustar($item, (float) $ajuste['nueva_existencia'], $motivo, $actor);
                }
            }

            return $movimientos;
        });
    }

    /**
     * Obtiene el listado de productos con existencias en nivel crítico o inferior al mínimo (CA-28).
     *
     * @return Collection<int, ItemMenu>
     */
    public static function alertasStockMinimo(int $sucursalId): Collection
    {
        return ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->where('controla_inventario', true)
            ->whereColumn('existencia', '<=', 'existencia_minima')
            ->orderBy('existencia')
            ->get();
    }

    /**
     * Obtiene el modo de inventario de la sucursal (AUTONOMO o DEPENDIENTE).
     */
    public static function obtenerModo(int $sucursalId): ModoInventario
    {
        $modoStr = (string) ConfiguracionService::obtener('inventario.modo', $sucursalId, ModoInventario::AUTONOMO->value);

        return ModoInventario::tryFrom($modoStr) ?? ModoInventario::AUTONOMO;
    }

    /**
     * Actualiza el modo de inventario (restringido a super_admin).
     */
    public static function establecerModo(int $sucursalId, ModoInventario $modo): void
    {
        ConfiguracionService::set('inventario.modo', $modo->value, $sucursalId);
    }
}
