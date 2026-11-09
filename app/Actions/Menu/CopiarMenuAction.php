<?php

namespace App\Actions\Menu;

use App\Events\MenuActualizado;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\ItemMenu;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CopiarMenuAction
{
    /**
     * Copia categorías, productos y combos de una sucursal origen a otra sucursal destino (RF-MEN-09, CA-25).
     *
     * @return array{categorias_copiadas: int, items_copiados: int, combos_copiados: int}
     *
     * @throws ValidationException
     */
    public function handle(int $sucursalOrigenId, int $sucursalDestinoId): array
    {
        if ($sucursalOrigenId === $sucursalDestinoId) {
            throw ValidationException::withMessages([
                'error' => 'La sucursal origen y destino no pueden ser la misma.',
            ]);
        }

        $sucursalOrigen = Sucursal::findOrFail($sucursalOrigenId);
        $sucursalDestino = Sucursal::findOrFail($sucursalDestinoId);

        return DB::transaction(function () use ($sucursalOrigen, $sucursalDestino) {
            $categoriasOrigen = Categoria::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalOrigen->id)
                ->get();

            $mapeoCategorias = [];
            $categoriasCopiadas = 0;

            foreach ($categoriasOrigen as $catOrigen) {
                // Buscar si en destino ya existe una categoría con el mismo slug
                $catDestino = Categoria::withoutGlobalScopes()
                    ->where('sucursal_id', $sucursalDestino->id)
                    ->where('slug', $catOrigen->slug)
                    ->first();

                if (! $catDestino) {
                    $catDestino = Categoria::create([
                        'sucursal_id' => $sucursalDestino->id,
                        'nombre' => $catOrigen->nombre,
                        'slug' => $catOrigen->slug,
                        'descripcion' => $catOrigen->descripcion,
                        'orden' => $catOrigen->orden,
                        'icono' => $catOrigen->icono,
                        'activa' => $catOrigen->activa,
                    ]);
                    $categoriasCopiadas++;
                }

                $mapeoCategorias[$catOrigen->id] = $catDestino->id;
            }

            // Copiar productos regulares primero (no combos)
            $itemsRegularesOrigen = ItemMenu::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalOrigen->id)
                ->where('es_combo', false)
                ->get();

            $mapeoItems = [];
            $itemsCopiados = 0;

            foreach ($itemsRegularesOrigen as $itemOrigen) {
                $categoriaDestinoId = $mapeoCategorias[$itemOrigen->categoria_id] ?? null;
                if (! $categoriaDestinoId) {
                    continue;
                }

                // Generar slug único para destino
                $itemDestino = ItemMenu::create([
                    'sucursal_id' => $sucursalDestino->id,
                    'categoria_id' => $categoriaDestinoId,
                    'nombre' => $itemOrigen->nombre,
                    'slug' => $itemOrigen->slug,
                    'descripcion' => $itemOrigen->descripcion,
                    'precio' => $itemOrigen->precio,
                    'impuesto_tipo' => $itemOrigen->impuesto_tipo,
                    'impuesto_porcentaje' => $itemOrigen->impuesto_porcentaje,
                    'imagen' => $itemOrigen->imagen,
                    'orden' => $itemOrigen->orden,
                    'es_combo' => false,
                    'controla_inventario' => $itemOrigen->controla_inventario,
                    'existencia' => $itemOrigen->existencia,
                    'existencia_minima' => $itemOrigen->existencia_minima,
                    'disponible' => $itemOrigen->disponible,
                    'activo' => $itemOrigen->activo,
                ]);

                $mapeoItems[$itemOrigen->id] = $itemDestino->id;
                $itemsCopiados++;
            }

            // Copiar combos y sus componentes
            $combosOrigen = ItemMenu::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalOrigen->id)
                ->where('es_combo', true)
                ->with(['comboItems'])
                ->get();

            $combosCopiados = 0;

            foreach ($combosOrigen as $comboOrigen) {
                $categoriaDestinoId = $mapeoCategorias[$comboOrigen->categoria_id] ?? null;
                if (! $categoriaDestinoId) {
                    continue;
                }

                $comboDestino = ItemMenu::create([
                    'sucursal_id' => $sucursalDestino->id,
                    'categoria_id' => $categoriaDestinoId,
                    'nombre' => $comboOrigen->nombre,
                    'slug' => $comboOrigen->slug,
                    'descripcion' => $comboOrigen->descripcion,
                    'precio' => $comboOrigen->precio,
                    'impuesto_tipo' => $comboOrigen->impuesto_tipo,
                    'impuesto_porcentaje' => $comboOrigen->impuesto_porcentaje,
                    'imagen' => $comboOrigen->imagen,
                    'orden' => $comboOrigen->orden,
                    'es_combo' => true,
                    'controla_inventario' => $comboOrigen->controla_inventario,
                    'existencia' => $comboOrigen->existencia,
                    'existencia_minima' => $comboOrigen->existencia_minima,
                    'disponible' => $comboOrigen->disponible,
                    'activo' => $comboOrigen->activo,
                ]);

                // Asignar los items componentes del combo adaptando al nuevo mapeo
                foreach ($comboOrigen->comboItems as $comboItemOrigen) {
                    $nuevoItemId = $mapeoItems[$comboItemOrigen->item_id] ?? null;
                    if ($nuevoItemId) {
                        ComboItem::create([
                            'combo_id' => $comboDestino->id,
                            'item_id' => $nuevoItemId,
                            'cantidad' => $comboItemOrigen->cantidad,
                        ]);
                    }
                }

                $combosCopiados++;
            }

            event(new MenuActualizado($sucursalDestino->id, 'completo', 'Menú copiado desde otra sede.'));

            return [
                'categorias_copiadas' => $categoriasCopiadas,
                'items_copiados' => $itemsCopiados,
                'combos_copiados' => $combosCopiados,
            ];
        });
    }
}
