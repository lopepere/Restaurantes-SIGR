<?php

namespace App\Services;

use App\Events\MenuActualizado;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\ItemMenu;
use App\Models\Promocion;
use App\Models\Sucursal;
use Illuminate\Support\Collection;

class MenuService
{
    public function __construct(
        protected ConfiguracionService $configuracionService
    ) {}

    /**
     * Retorna la estructura completa del menú público de una sucursal con promociones y agotados (RF-PUB-04, 05, 08, CA-26).
     *
     * @return array{
     *     sucursal: array{id: int, nombre: string, slug: string, moneda: string, telefono: string, direccion: string},
     *     promociones: array<int, array<string, mixed>>,
     *     categorias: array<int, array<string, mixed>>,
     *     modo_agotados: string
     * }
     */
    public function obtenerMenuPublico(int $sucursalId, ?string $busqueda = null): array
    {
        $sucursal = Sucursal::findOrFail($sucursalId);

        // Parámetro de productos agotados: 'gris' (por defecto) u 'ocultar' (CA-26, RF-PUB-08)
        $modoAgotados = (string) $this->configuracionService->obtener('menu.productos_agotados', $sucursalId, 'gris');

        // Cargar promociones publicadas y vigentes
        $promocionesVigentes = $this->obtenerPromocionesVigentes($sucursalId);

        // Consultar categorías activas de la sucursal
        $categorias = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activa', true)
            ->orderBy('orden')
            ->with(['items' => function ($q) use ($busqueda) {
                $q->withoutGlobalScopes()
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->with(['comboItems.item']);

                if ($busqueda && trim($busqueda) !== '') {
                    $term = '%'.trim($busqueda).'%';
                    $q->where(function ($sub) use ($term) {
                        $sub->where('nombre', 'like', $term)
                            ->orWhere('descripcion', 'like', $term);
                    });
                }
            }])
            ->get();

        $categoriasFormateadas = [];

        foreach ($categorias as $categoria) {
            $itemsFormateados = [];

            foreach ($categoria->items as $item) {
                $estaAgotado = $item->estaAgotado();

                // Si el parámetro está en 'ocultar' y el ítem está agotado, se excluye de la vista pública
                if ($modoAgotados === 'ocultar' && $estaAgotado) {
                    continue;
                }

                // Evaluar la mejor promoción aplicable
                $precioOriginal = (float) $item->precio;
                $mejorPrecioPromocional = null;
                $promocionAplicada = null;

                foreach ($promocionesVigentes as $promo) {
                    $aplicaPorCategoria = ! $item->excluir_descuentos_categoria && $promo->categorias->contains('id', $categoria->id);
                    $aplica = $promo->aplica_a_todo
                        || $promo->items->contains('id', $item->id)
                        || $aplicaPorCategoria;

                    if ($aplica) {
                        $precioCalculado = $promo->calcularPrecioPromocional($precioOriginal);
                        if ($mejorPrecioPromocional === null || $precioCalculado < $mejorPrecioPromocional) {
                            $mejorPrecioPromocional = $precioCalculado;
                            $promocionAplicada = [
                                'id' => $promo->id,
                                'nombre' => $promo->nombre,
                                'tipo_beneficio' => $promo->tipo_beneficio->value,
                                'valor_beneficio' => (float) $promo->valor_beneficio,
                            ];
                        }
                    }
                }

                $desgloseImpuesto = MonedaService::desglosarImpuestoIncluido(
                    $precioOriginal,
                    (float) $item->impuesto_porcentaje
                );

                $itemsFormateados[] = [
                    'id' => $item->id,
                    'categoria_id' => $item->categoria_id,
                    'nombre' => $item->nombre,
                    'slug' => $item->slug,
                    'descripcion' => $item->descripcion,
                    'precio' => $precioOriginal,
                    'precio_formateado' => MonedaService::formatear($precioOriginal, $sucursal->moneda),
                    'precio_promocional' => $mejorPrecioPromocional,
                    'precio_promocional_formateado' => $mejorPrecioPromocional !== null ? MonedaService::formatear($mejorPrecioPromocional, $sucursal->moneda) : null,
                    'promocion' => $promocionAplicada,
                    'impuesto_tipo' => $item->impuesto_tipo->value,
                    'impuesto_porcentaje' => (float) $item->impuesto_porcentaje,
                    'base_imponible' => $desgloseImpuesto['base'],
                    'valor_impuesto' => $desgloseImpuesto['impuesto'],
                    'imagen' => $item->imagen,
                    'es_combo' => $item->es_combo,
                    'items_del_combo' => $item->es_combo ? $item->comboItems->map(fn (ComboItem $sub) => [
                        'id' => $sub->item_id,
                        'nombre' => $sub->item->nombre,
                        'cantidad' => (float) $sub->cantidad,
                    ])->values()->all() : [],
                    'controla_inventario' => $item->controla_inventario,
                    'existencia' => (float) $item->existencia,
                    'disponible' => $item->disponible,
                    'esta_agotado' => $estaAgotado,
                ];
            }

            // Si hay búsqueda y no hay ítems en la categoría, omitir
            if (! empty($busqueda) && count($itemsFormateados) === 0) {
                continue;
            }

            $categoriasFormateadas[] = [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
                'descripcion' => $categoria->descripcion,
                'icono' => $categoria->icono,
                'items' => $itemsFormateados,
            ];
        }

        $promocionesFormateadas = $promocionesVigentes->map(function (Promocion $promo) {
            return [
                'id' => $promo->id,
                'nombre' => $promo->nombre,
                'slug' => $promo->slug,
                'descripcion' => $promo->descripcion,
                'tipo_beneficio' => $promo->tipo_beneficio->value,
                'valor_beneficio' => (float) $promo->valor_beneficio,
                'imagen' => $promo->imagen,
                'fecha_fin' => $promo->fecha_fin->toIso8601String(),
            ];
        })->values()->all();

        return [
            'sucursal' => [
                'id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
                'slug' => $sucursal->slug,
                'moneda' => $sucursal->moneda,
                'telefono' => $sucursal->telefono,
                'direccion' => $sucursal->direccion,
            ],
            'promociones' => $promocionesFormateadas,
            'categorias' => $categoriasFormateadas,
            'modo_agotados' => $modoAgotados,
        ];
    }

    /**
     * Obtiene la colección de promociones vigentes de la sucursal.
     *
     * @return Collection<int, Promocion>
     */
    public function obtenerPromocionesVigentes(int $sucursalId): Collection
    {
        return Promocion::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activa', true)
            ->where('publicada', true)
            ->with(['items', 'categorias'])
            ->get()
            ->filter(fn (Promocion $p) => $p->estaVigente())
            ->values();
    }

    /**
     * Cambia la disponibilidad inmediata de un producto y notifica por WebSockets (RF-MEN-08).
     */
    public function alternarDisponibilidad(ItemMenu $item): ItemMenu
    {
        $item->update([
            'disponible' => ! $item->disponible,
        ]);

        event(new MenuActualizado($item->sucursal_id, 'item', "Disponibilidad de {$item->nombre} actualizada."));

        return $item;
    }
}
