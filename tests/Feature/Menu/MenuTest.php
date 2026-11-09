<?php

use App\Actions\Menu\CopiarMenuAction;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\Configuracion;
use App\Models\ItemMenu;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\MenuService;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursalCentro = Sucursal::create([
        'nombre' => 'Sede Centro Test',
        'slug' => 'sede-centro-test',
        'direccion' => 'Calle 10 # 4-20',
        'telefono' => '+573001234567',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->adminCentro = User::create([
        'nombre' => 'Admin Centro',
        'email' => 'admin.centro.menu@sigr.test',
        'telefono' => '+573005551122',
        'direccion' => 'Calle 10',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->adminCentro->assignRole('admin');
    $this->adminCentro->sucursales()->attach($this->sucursalCentro->id);
});

test('observer de sucursal crea automaticamente las 4 categorias base (DE-07, RF-MEN-01)', function () {
    // Al crearse $this->sucursalCentro en beforeEach, el observer ya debió ejecutarse
    $categorias = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->orderBy('orden')
        ->get();

    expect($categorias)->toHaveCount(4);
    expect($categorias->pluck('nombre')->all())->toBe([
        'Entradas',
        'Platos Fuertes',
        'Bebidas',
        'Postres',
    ]);
});

test('rechaza borrado logico de categoria si tiene items de menu activos (RF-AUD-05, RN-GEN-04)', function () {
    $this->actingAs($this->adminCentro);
    session(['sucursal_id' => $this->sucursalCentro->id]);

    $categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->first();

    // Crear un producto asociado
    ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Empanada de Carne',
        'slug' => 'empanada-de-carne',
        'precio' => 5000.00,
        'activo' => true,
    ]);

    // Intentar eliminar la categoría
    $response = $this->delete(route('admin.menu.categorias.destroy', $categoria));

    $response->assertSessionHasErrors(['error']);
    expect($categoria->fresh()->trashed())->toBeFalse();
});

test('rechaza borrado logico de item si es componente de un combo activo (RF-AUD-05, RN-GEN-04)', function () {
    $this->actingAs($this->adminCentro);
    session(['sucursal_id' => $this->sucursalCentro->id]);

    $categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->first();

    $itemSimple = ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Papas Fritas',
        'slug' => 'papas-fritas',
        'precio' => 6000.00,
        'activo' => true,
    ]);

    $combo = ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Combo Super',
        'slug' => 'combo-super',
        'precio' => 15000.00,
        'es_combo' => true,
        'activo' => true,
    ]);

    ComboItem::create([
        'combo_id' => $combo->id,
        'item_id' => $itemSimple->id,
        'cantidad' => 1.00,
    ]);

    // Intentar eliminar $itemSimple debe ser rechazado
    $response = $this->delete(route('admin.menu.items.destroy', $itemSimple));

    $response->assertSessionHasErrors(['error']);
    expect($itemSimple->fresh()->trashed())->toBeFalse();
});

test('CA-25 CopiarMenuAction clona fielmente categorias, items y combos a otra sucursal', function () {
    // 1. Crear productos en sucursal centro
    $categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->where('slug', 'entradas')
        ->first();

    $producto1 = ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Patacones de Origen',
        'slug' => 'patacones-de-origen',
        'precio' => 14000.00,
        'controla_inventario' => true,
        'existencia' => 20,
        'activo' => true,
    ]);

    $combo = ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Combo Doble Patacon',
        'slug' => 'combo-doble-patacon',
        'precio' => 25000.00,
        'es_combo' => true,
        'controla_inventario' => false,
        'activo' => true,
    ]);

    ComboItem::create([
        'combo_id' => $combo->id,
        'item_id' => $producto1->id,
        'cantidad' => 2.00,
    ]);

    // 2. Crear segunda sucursal vacía
    $sucursalNorte = Sucursal::create([
        'nombre' => 'Sede Norte Destino',
        'slug' => 'sede-norte-destino',
        'direccion' => 'Calle 100',
        'telefono' => '+573009991122',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    // 3. Ejecutar acción de copia
    $action = app(CopiarMenuAction::class);
    $resultado = $action->handle($this->sucursalCentro->id, $sucursalNorte->id);

    expect($resultado['items_copiados'])->toBe(1);
    expect($resultado['combos_copiados'])->toBe(1);

    // 4. Verificar existencia en la sede norte
    $itemsNorte = ItemMenu::withoutGlobalScopes()
        ->where('sucursal_id', $sucursalNorte->id)
        ->with(['comboItems.item'])
        ->get();
    expect($itemsNorte)->toHaveCount(2);

    $comboNorte = $itemsNorte->firstWhere('es_combo', true);
    expect($comboNorte)->not->toBeNull();
    expect($comboNorte->comboItems)->toHaveCount(1);
    expect($comboNorte->comboItems->first()->cantidad)->toBe('2.00');
    // El item vinculado debe pertenecer a la sede norte, no a la centro
    expect($comboNorte->comboItems->first()->item->sucursal_id)->toBe($sucursalNorte->id);
});

test('CA-26 productos agotados se muestran en gris u ocultos segun configuracion', function () {
    $menuService = app(MenuService::class);

    $categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->first();

    // Producto agotado (controla inventario con stock 0)
    $productoAgotado = ItemMenu::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Producto Sin Stock',
        'slug' => 'producto-sin-stock',
        'precio' => 10000.00,
        'controla_inventario' => true,
        'existencia' => 0.00,
        'disponible' => true,
        'activo' => true,
    ]);

    // Caso A: Parámetro 'gris' (por defecto) -> el producto aparece en el menú con bandera esta_agotado = true
    Configuracion::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'clave' => 'menu.productos_agotados',
        'valor' => 'gris',
    ]);

    $menuGris = $menuService->obtenerMenuPublico($this->sucursalCentro->id);
    $catGris = collect($menuGris['categorias'])->firstWhere('id', $categoria->id);
    $itemEnGris = collect($catGris['items'])->firstWhere('id', $productoAgotado->id);

    expect($itemEnGris)->not->toBeNull();
    expect($itemEnGris['esta_agotado'])->toBeTrue();

    // Caso B: Parámetro 'ocultar' -> el producto no debe aparecer en el listado público
    Configuracion::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursalCentro->id)
        ->where('clave', 'menu.productos_agotados')
        ->update(['valor' => json_encode('ocultar')]);

    // Limpiar caché de configuración
    cache()->flush();

    $menuOcultar = $menuService->obtenerMenuPublico($this->sucursalCentro->id);
    $catOcultar = collect($menuOcultar['categorias'])->firstWhere('id', $categoria->id);
    $itemOculto = collect($catOcultar['items'] ?? [])->firstWhere('id', $productoAgotado->id);

    expect($itemOculto)->toBeNull();
});
