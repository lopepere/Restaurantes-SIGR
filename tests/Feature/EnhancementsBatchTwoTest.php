<?php

use App\Actions\Compras\DevolverCompraAction;
use App\Enums\EstadoCompra;
use App\Enums\EstadoPedido;
use App\Enums\TipoImpuesto;
use App\Enums\TipoPedido;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\MenuService;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Batch Two',
        'slug' => 'sede-batch-two',
        'direccion' => 'Carrera 43A # 1-50',
        'telefono' => '+573009998877',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
        'acepta_domicilios' => true,
        'acepta_para_llevar' => true,
        'acepta_en_sitio' => true,
    ]);

    $this->superadmin = User::factory()->create();
    $this->superadmin->assignRole('super_admin');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->cajero = User::factory()->create();
    $this->cajero->assignRole('cajero');
    $this->cajero->sucursales()->attach($this->sucursal->id);

    $this->cocinero = User::factory()->create();
    $this->cocinero->assignRole('cocinero');
    $this->cocinero->sucursales()->attach($this->sucursal->id);

    $this->caja = Caja::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Caja Central',
        'activa' => true,
    ]);
});

test('SuperAdmin puede configurar catálogo de impuestos dinámicos (Item 1)', function () {
    $nuevoCatalogo = [
        ['id' => 'inc_8', 'codigo' => 'INC8', 'nombre' => 'Impoconsumo Restaurante', 'porcentaje' => 8, 'descripcion' => 'Ley 2277', 'activo' => true],
        ['id' => 'iva_19', 'codigo' => 'IVA19', 'nombre' => 'IVA General', 'porcentaje' => 19, 'descripcion' => 'Bebidas alcohólicas', 'activo' => true],
        ['id' => 'exento_0', 'codigo' => 'EXENTO', 'nombre' => 'Exento / 0%', 'porcentaje' => 0, 'descripcion' => 'Sin impuesto', 'activo' => true],
        ['id' => 'turismo_5', 'codigo' => 'TURISMO', 'nombre' => 'Tasa Especial Turismo', 'porcentaje' => 5, 'descripcion' => 'Hotelera', 'activo' => false],
    ];

    $response = $this->actingAs($this->superadmin)->post(route('superadmin.impuestos.catalogo'), [
        'catalogo' => $nuevoCatalogo,
    ]);

    $response->assertRedirect();
    $service = app(ConfiguracionService::class);
    $guardado = $service->catalogoImpuestos();

    expect($guardado)->toHaveCount(4)
        ->and($guardado[3]['codigo'])->toBe('TURISMO')
        ->and($guardado[3]['activo'])->toBeFalse();
});

test('SuperAdmin y Admin pueden exportar informes en formato Excel (CSV UTF-8 BOM) y PDF (Item 2)', function () {
    $responseExcel = $this->actingAs($this->admin)->get(route('admin.informes.exportar.excel', [
        'sucursal_id' => $this->sucursal->id,
        'fecha_desde' => now()->startOfMonth()->toDateString(),
        'fecha_hasta' => now()->toDateString(),
    ]));

    $responseExcel->assertSuccessful();
    expect($responseExcel->headers->get('content-type'))->toContain('text/csv');

    $responsePdf = $this->actingAs($this->admin)->get(route('admin.informes.exportar.pdf', [
        'sucursal_id' => $this->sucursal->id,
        'fecha_desde' => now()->startOfMonth()->toDateString(),
        'fecha_hasta' => now()->toDateString(),
    ]));

    $responsePdf->assertSuccessful();
    expect($responsePdf->headers->get('content-type'))->toContain('application/pdf');
});

test('SuperAdmin puede alternar modalidades de pedido en sucursal (Item 9)', function () {
    expect($this->sucursal->acepta_domicilios)->toBeTrue();

    $response = $this->actingAs($this->superadmin)->post(route('superadmin.sucursales.modalidad', $this->sucursal), [
        'modalidad' => 'domicilio',
    ]);

    $response->assertRedirect();
    $this->sucursal->refresh();
    expect($this->sucursal->acepta_domicilios)->toBeFalse();
});

test('MenuService respeta la exclusion de promociones de categoria en items especificos (Item 8)', function () {
    $categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Platos Especiales',
        'slug' => 'platos-especiales',
        'orden' => 1,
        'activa' => true,
    ]);

    $itemConDescuento = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Plato Con Descuento',
        'slug' => 'plato-con-descuento',
        'precio' => 20000,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'controla_inventario' => false,
        'disponible' => true,
        'orden' => 1,
        'excluir_descuentos_categoria' => false,
        'activo' => true,
    ]);

    $itemExcluido = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Plato Excluido',
        'slug' => 'plato-excluido',
        'precio' => 20000,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'controla_inventario' => false,
        'disponible' => true,
        'orden' => 2,
        'excluir_descuentos_categoria' => true,
        'activo' => true,
    ]);

    $promo = Promocion::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => '20% OFF Categoría',
        'slug' => '20-off-categoria',
        'tipo_beneficio' => 'porcentaje',
        'valor_beneficio' => 20,
        'aplica_a_todo' => false,
        'fecha_inicio' => now()->subDay()->toDateString(),
        'fecha_fin' => now()->addMonth()->toDateString(),
        'dias_semana' => [0, 1, 2, 3, 4, 5, 6],
        'activa' => true,
        'publicada' => true,
    ]);
    $promo->categorias()->attach($categoria->id);

    $menuService = app(MenuService::class);
    $menu = $menuService->obtenerMenuPublico($this->sucursal->id);
    $cat = collect($menu['categorias'])->firstWhere('id', $categoria->id);
    $items = collect($cat['items']);
    $itemConDesc = $items->firstWhere('id', $itemConDescuento->id);
    $itemExc = $items->firstWhere('id', $itemExcluido->id);

    expect((float) $itemConDesc['precio_promocional'])->toBe(16000.0)
        ->and($itemExc['precio_promocional'])->toBeNull();
});

test('Devolución parcial de compra revierte inventario del ítem devuelto y marca compra como DEVUELTA_PARCIAL (Item 4)', function () {
    $proveedor = Proveedor::create([
        'sucursal_id' => $this->sucursal->id,
        'razon_social' => 'Distribuidora Carnes SAS',
        'nit' => '900123456-1',
        'telefono' => '+573001112233',
        'email' => 'ventas@carnes.test',
        'activo' => true,
    ]);

    $categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Carnes',
        'slug' => 'carnes',
        'orden' => 1,
        'activa' => true,
    ]);

    $itemA = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Lomo Fino Res',
        'slug' => 'lomo-fino-res',
        'precio' => 30000,
        'controla_inventario' => true,
        'existencia' => 10,
        'existencia_minima' => 2,
        'activo' => true,
    ]);

    $itemB = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Pechuga Pollo',
        'slug' => 'pechuga-pollo',
        'precio' => 15000,
        'controla_inventario' => true,
        'existencia' => 10,
        'existencia_minima' => 2,
        'activo' => true,
    ]);

    $compra = Compra::create([
        'sucursal_id' => $this->sucursal->id,
        'proveedor_id' => $proveedor->id,
        'user_id' => $this->admin->id,
        'numero_factura' => 'FAC-9988',
        'fecha_compra' => now()->toDateString(),
        'subtotal' => 450000,
        'impuesto_total' => 0,
        'total' => 450000,
        'estado' => EstadoCompra::REGISTRADA,
    ]);

    $compraItemA = CompraItem::create([
        'compra_id' => $compra->id,
        'item_menu_id' => $itemA->id,
        'descripcion' => 'Lomo Fino Res',
        'cantidad' => 10,
        'costo_unitario' => 30000,
        'factor_conversion' => 1,
        'impuesto_porcentaje' => 0,
        'subtotal' => 300000,
        'impuesto_valor' => 0,
        'total' => 300000,
    ]);

    $compraItemB = CompraItem::create([
        'compra_id' => $compra->id,
        'item_menu_id' => $itemB->id,
        'descripcion' => 'Pechuga Pollo',
        'cantidad' => 10,
        'costo_unitario' => 15000,
        'factor_conversion' => 1,
        'impuesto_porcentaje' => 0,
        'subtotal' => 150000,
        'impuesto_valor' => 0,
        'total' => 150000,
    ]);

    // Devolución parcial de 4 unidades de Lomo Fino
    $action = app(DevolverCompraAction::class);
    $devolucion = $action->handle(
        $compra,
        'Producto no cumplió especificación de empaque al vacío',
        [['compra_item_id' => $compraItemA->id, 'cantidad' => 4]],
        $this->admin
    );

    $compra->refresh();
    $itemA->refresh();
    $itemB->refresh();

    expect($compra->estado)->toBe(EstadoCompra::DEVUELTA_PARCIAL)
        ->and((float) $itemA->existencia)->toBe(6.0) // 10 - 4
        ->and((float) $itemB->existencia)->toBe(10.0) // intacto
        ->and((float) $devolucion->total_devuelto)->toBe(120000.0);
});

test('Usuario cajero accede a /cajero y usuario cocinero accede a /cocina (Item 6)', function () {
    $resCajero = $this->actingAs($this->cajero)->get('/cajero');
    $resCajero->assertSuccessful();

    $resCocina = $this->actingAs($this->cocinero)->get('/cocina');
    $resCocina->assertSuccessful();
});

test('Cajero/Admin puede anular ítem de un pedido en curso con auditoría (Item 11)', function () {
    $categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Bebidas',
        'slug' => 'bebidas',
        'orden' => 1,
        'activa' => true,
    ]);

    $itemMenu = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $categoria->id,
        'nombre' => 'Jugo Natural',
        'slug' => 'jugo-natural',
        'precio' => 8000,
        'activo' => true,
    ]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'mesero_id' => $this->admin->id,
        'numero' => 'PED-TEST-001',
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::EN_PREPARACION,
        'subtotal' => 16000,
        'impuestos' => 1280,
        'total' => 17280,
    ]);

    $pedidoItem = PedidoItem::create([
        'pedido_id' => $pedido->id,
        'item_menu_id' => $itemMenu->id,
        'nombre_item' => 'Jugo Natural',
        'cantidad' => 2,
        'precio_unitario' => 8000,
        'subtotal' => 16000,
        'impuesto_tipo' => 'inc',
        'impuesto_porcentaje' => 8,
        'impuesto_monto' => 1280,
        'total' => 17280,
        'anulado' => false,
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.caja.items.anular', [$pedido, $pedidoItem]), [
        'motivo' => 'Cliente canceló bebida antes de servir',
    ]);

    $response->assertRedirect();
    $pedidoItem->refresh();
    $pedido->refresh();

    expect($pedidoItem->anulado)->toBeTrue()
        ->and($pedidoItem->motivo_anulacion)->toBe('Cliente canceló bebida antes de servir')
        ->and((float) $pedido->total)->toBe(0.0);
});
