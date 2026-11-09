<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Actions\Menu\CopiarMenuAction;
use App\Enums\TipoImpuesto;
use App\Events\MenuActualizado;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\ItemMenu;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function __construct(
        protected MenuService $menuService,
        protected CopiarMenuAction $copiarMenuAction,
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Categoria::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $categorias = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->withCount('items')
            ->orderBy('orden')
            ->get();

        $items = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['categoria', 'itemsDelCombo'])
            ->when($request->input('categoria_id'), fn ($q, $cat) => $q->where('categoria_id', $cat))
            ->when($request->input('buscar'), function ($q, $buscar) {
                $term = "%{$buscar}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('nombre', 'like', $term)
                        ->orWhere('descripcion', 'like', $term);
                });
            })
            ->orderBy('orden')
            ->paginate(25);

        // Ítems individuales de la sucursal disponibles para armar combos
        $itemsParaCombo = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('es_combo', false)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'precio', 'existencia', 'controla_inventario']);

        // Otras sucursales para el modal de copiar menú
        $otrasSucursales = Sucursal::where('id', '!=', $sucursalId)
            ->where('activa', true)
            ->get(['id', 'nombre']);

        return Inertia::render('Admin/Menu/Index', [
            'categorias' => $categorias,
            'items' => $items,
            'itemsParaCombo' => $itemsParaCombo,
            'otrasSucursales' => $otrasSucursales,
            'filtros' => $request->only(['buscar', 'categoria_id']),
            'tiposImpuesto' => array_column(TipoImpuesto::cases(), 'value'),
            'catalogoImpuestos' => ConfiguracionService::catalogoImpuestos(),
        ]);
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        Gate::authorize('create', Categoria::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'icono' => ['nullable', 'string', 'max:50'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['nombre']);

        Categoria::create([
            'sucursal_id' => $sucursalId,
            'nombre' => $validated['nombre'],
            'slug' => $slug,
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? 0,
            'icono' => $validated['icono'] ?? null,
            'activa' => $validated['activa'] ?? true,
        ]);

        event(new MenuActualizado($sucursalId, 'categoria', 'Categoría creada.'));

        return back()->with('success', 'Categoría creada exitosamente.');
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        Gate::authorize('update', $categoria);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'orden' => ['required', 'integer', 'min:0'],
            'icono' => ['nullable', 'string', 'max:50'],
            'activa' => ['required', 'boolean'],
        ]);

        $categoria->update([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'],
            'icono' => $validated['icono'] ?? null,
            'activa' => $validated['activa'],
        ]);

        event(new MenuActualizado($categoria->sucursal_id, 'categoria', 'Categoría actualizada.'));

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroyCategoria(Categoria $categoria): RedirectResponse
    {
        Gate::authorize('delete', $categoria);

        // Valida que no tenga ítems asociados (RF-AUD-05, RN-GEN-04)
        $this->eliminarConValidacionAction->handle($categoria, ['items']);

        event(new MenuActualizado($categoria->sucursal_id, 'categoria', 'Categoría eliminada.'));

        return back()->with('success', 'Categoría enviada a la papelera.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItemMenu::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'min:0'],
            'impuesto_tipo' => ['required', new Enum(TipoImpuesto::class)],
            'impuesto_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'es_combo' => ['nullable', 'boolean'],
            'excluir_descuentos_categoria' => ['nullable', 'boolean'],
            'controla_inventario' => ['nullable', 'boolean'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0'],
            'disponible' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'combo_items' => ['nullable', 'array'],
            'combo_items.*.item_id' => ['required_with:combo_items', 'integer', 'exists:items_menu,id'],
            'combo_items.*.cantidad' => ['required_with:combo_items', 'numeric', 'min:1'],
        ]);

        $item = ItemMenu::create([
            'sucursal_id' => $sucursalId,
            'categoria_id' => $validated['categoria_id'],
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'precio' => $validated['precio'],
            'impuesto_tipo' => $validated['impuesto_tipo'],
            'impuesto_porcentaje' => $validated['impuesto_porcentaje'],
            'es_combo' => $validated['es_combo'] ?? false,
            'excluir_descuentos_categoria' => $validated['excluir_descuentos_categoria'] ?? false,
            'controla_inventario' => $validated['controla_inventario'] ?? true,
            'existencia' => $validated['existencia'] ?? 0,
            'existencia_minima' => $validated['existencia_minima'] ?? 5,
            'disponible' => $validated['disponible'] ?? true,
            'activo' => true,
            'orden' => $validated['orden'] ?? 0,
        ]);

        if (! empty($validated['es_combo']) && ! empty($validated['combo_items'])) {
            foreach ($validated['combo_items'] as $cItem) {
                ComboItem::create([
                    'combo_id' => $item->id,
                    'item_id' => $cItem['item_id'],
                    'cantidad' => $cItem['cantidad'],
                ]);
            }
        }

        event(new MenuActualizado($sucursalId, 'item', "Producto {$item->nombre} creado."));

        return back()->with('success', 'Producto agregado al menú.');
    }

    public function updateItem(Request $request, ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('update', $itemMenu);

        $validated = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'min:0'],
            'impuesto_tipo' => ['required', new Enum(TipoImpuesto::class)],
            'impuesto_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'es_combo' => ['required', 'boolean'],
            'excluir_descuentos_categoria' => ['nullable', 'boolean'],
            'controla_inventario' => ['required', 'boolean'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0'],
            'disponible' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
            'orden' => ['required', 'integer', 'min:0'],
            'combo_items' => ['nullable', 'array'],
            'combo_items.*.item_id' => ['required_with:combo_items', 'integer', 'exists:items_menu,id'],
            'combo_items.*.cantidad' => ['required_with:combo_items', 'numeric', 'min:1'],
        ]);

        $itemMenu->update([
            'categoria_id' => $validated['categoria_id'],
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'precio' => $validated['precio'],
            'impuesto_tipo' => $validated['impuesto_tipo'],
            'impuesto_porcentaje' => $validated['impuesto_porcentaje'],
            'es_combo' => $validated['es_combo'],
            'excluir_descuentos_categoria' => $validated['excluir_descuentos_categoria'] ?? false,
            'controla_inventario' => $validated['controla_inventario'],
            'existencia' => $validated['existencia'] ?? 0,
            'existencia_minima' => $validated['existencia_minima'] ?? 5,
            'disponible' => $validated['disponible'],
            'activo' => $validated['activo'],
            'orden' => $validated['orden'],
        ]);

        if ($validated['es_combo']) {
            ComboItem::where('combo_id', $itemMenu->id)->delete();
            if (! empty($validated['combo_items'])) {
                foreach ($validated['combo_items'] as $cItem) {
                    ComboItem::create([
                        'combo_id' => $itemMenu->id,
                        'item_id' => $cItem['item_id'],
                        'cantidad' => $cItem['cantidad'],
                    ]);
                }
            }
        }

        event(new MenuActualizado($itemMenu->sucursal_id, 'item', "Producto {$itemMenu->nombre} actualizado."));

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroyItem(ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('delete', $itemMenu);

        // Valida que el ítem no participe como ingrediente en ningún combo activo
        $this->eliminarConValidacionAction->handle($itemMenu, ['combosDondeAparece']);

        event(new MenuActualizado($itemMenu->sucursal_id, 'item', "Producto {$itemMenu->nombre} eliminado."));

        return back()->with('success', 'Producto enviado a la papelera.');
    }

    public function toggleDisponibilidad(ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('update', $itemMenu);

        $this->menuService->alternarDisponibilidad($itemMenu);

        return back()->with('success', "Disponibilidad de {$itemMenu->nombre} modificada.");
    }

    public function copiarMenu(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItemMenu::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalDestinoId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'sucursal_origen_id' => [
                'required',
                'integer',
                'exists:sucursales,id',
                Rule::notIn([$sucursalDestinoId]),
            ],
        ]);

        $resultado = $this->copiarMenuAction->handle($validated['sucursal_origen_id'], $sucursalDestinoId);

        return back()->with('success', "Menú copiado exitosamente: {$resultado['categorias_copiadas']} categorías, {$resultado['items_copiados']} productos y {$resultado['combos_copiados']} combos.");
    }
}
