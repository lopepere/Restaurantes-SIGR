<?php

use App\Enums\EstadoCompra;
use App\Enums\TipoImpuesto;
use App\Enums\TipoMovimiento;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\DevolucionCompra;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Compras Test',
        'slug' => 'sede-compras-test',
        'direccion' => 'Calle 93 # 12-45',
        'telefono' => '+573003334455',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->proveedor = Proveedor::create([
        'sucursal_id' => $this->sucursal->id,
        'razon_social' => 'Distribuidora Avícola S.A.S.',
        'nit' => '900.123.456-7',
        'telefono' => '3109998877',
        'email' => 'ventas@avicola.test',
        'activo' => true,
    ]);

    $this->categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Carnes y Aves',
        'slug' => 'carnes-y-aves',
        'orden' => 1,
        'activa' => true,
    ]);

    $this->item = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Pechuga Fileteada',
        'slug' => 'pechuga-fileteada',
        'precio' => 22000,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'orden' => 1,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 5,
        'existencia_minima' => 2,
        'disponible' => true,
        'activo' => true,
    ]);
});

test('puede registrar compra calculando impuestos e incrementando existencias por factor de conversion (RF-COM-02, RF-COM-03)', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.compras.store'), [
        'sucursal_id' => $this->sucursal->id,
        'proveedor_id' => $this->proveedor->id,
        'numero_factura' => 'FAC-PROV-9988',
        'fecha_compra' => now()->toDateString(),
        'incluye_impuesto' => false,
        'impuesto_porcentaje' => 19,
        'notas' => 'Pedido semanal de carnes',
        'items' => [
            [
                'item_menu_id' => $this->item->id,
                'descripcion' => 'Caja x 5 Kg Pechuga (factor 5)',
                'cantidad' => 2,
                'costo_unitario' => 50000,
                'factor_conversion' => 5.0,
            ],
        ],
    ]);

    $response->assertRedirect();

    $compra = Compra::where('numero_factura', 'FAC-PROV-9988')->firstOrFail();
    expect($compra->estado)->toBe(EstadoCompra::REGISTRADA)
        ->and((float) $compra->subtotal)->toBe(100000.0)
        ->and((float) $compra->impuesto_total)->toBe(19000.0)
        ->and((float) $compra->total)->toBe(119000.0);

    // Existencias: 5 iniciales + (2 cajas * factor 5 = 10 unidades) = 15
    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(15.0);

    // Kárdex
    $kardex = MovimientoInventario::where('item_menu_id', $this->item->id)
        ->where('tipo', TipoMovimiento::COMPRA)
        ->first();

    expect($kardex)->not->toBeNull()
        ->and((float) $kardex->cantidad)->toBe(10.0)
        ->and((float) $kardex->saldo_cantidad)->toBe(15.0);
});

test('puede registrar compra sin impuestos por defecto (RF-COM-03)', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.compras.store'), [
        'sucursal_id' => $this->sucursal->id,
        'proveedor_id' => $this->proveedor->id,
        'numero_factura' => 'FAC-PROV-EXENTA-01',
        'fecha_compra' => now()->toDateString(),
        'incluye_impuesto' => false,
        'impuesto_porcentaje' => 0,
        'items' => [
            [
                'item_menu_id' => $this->item->id,
                'descripcion' => 'Pechuga a granel',
                'cantidad' => 4,
                'costo_unitario' => 15000,
                'factor_conversion' => 1.0,
            ],
        ],
    ]);

    $response->assertRedirect();

    $compra = Compra::where('numero_factura', 'FAC-PROV-EXENTA-01')->firstOrFail();
    expect((float) $compra->subtotal)->toBe(60000.0)
        ->and((float) $compra->impuesto_total)->toBe(0.0)
        ->and((float) $compra->total)->toBe(60000.0);
});

test('puede anular una compra registrada y revierte existencias en el inventario (RF-COM-06)', function () {
    $this->actingAs($this->admin)->post(route('admin.compras.store'), [
        'sucursal_id' => $this->sucursal->id,
        'proveedor_id' => $this->proveedor->id,
        'numero_factura' => 'FAC-A-ANULAR',
        'fecha_compra' => now()->toDateString(),
        'items' => [
            [
                'item_menu_id' => $this->item->id,
                'descripcion' => 'Pechuga para anular',
                'cantidad' => 6,
                'costo_unitario' => 12000,
                'factor_conversion' => 1.0,
            ],
        ],
    ]);

    $compra = Compra::where('numero_factura', 'FAC-A-ANULAR')->firstOrFail();
    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(11.0); // 5 + 6

    // Anular compra
    $respAnular = $this->actingAs($this->admin)->post(route('admin.compras.anular', $compra->id), [
        'motivo' => 'Factura con NIT erróneo y pedido rechazado en muelle',
    ]);
    $respAnular->assertRedirect();

    $compra->refresh();
    expect($compra->estado)->toBe(EstadoCompra::ANULADA);

    // Existencias revertidas
    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(5.0);

    // Kárdex de anulación
    $kardex = MovimientoInventario::where('item_menu_id', $this->item->id)
        ->where('tipo', TipoMovimiento::ANULACION)
        ->first();
    expect($kardex)->not->toBeNull()
        ->and((float) $kardex->cantidad)->toBe(6.0)
        ->and((float) $kardex->saldo_cantidad)->toBe(5.0);
});

test('puede devolver una compra registrada con registro formal y reversion de existencias (RF-COM-07)', function () {
    $this->actingAs($this->admin)->post(route('admin.compras.store'), [
        'sucursal_id' => $this->sucursal->id,
        'proveedor_id' => $this->proveedor->id,
        'numero_factura' => 'FAC-A-DEVOLVER',
        'fecha_compra' => now()->toDateString(),
        'items' => [
            [
                'item_menu_id' => $this->item->id,
                'descripcion' => 'Pechuga defectuosa',
                'cantidad' => 3,
                'costo_unitario' => 12000,
                'factor_conversion' => 1.0,
            ],
        ],
    ]);

    $compra = Compra::where('numero_factura', 'FAC-A-DEVOLVER')->firstOrFail();
    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(8.0); // 5 + 3

    // Devolver compra
    $respDevolver = $this->actingAs($this->admin)->post(route('admin.compras.devolver', $compra->id), [
        'motivo' => 'Cadena de frío rota en transporte del proveedor',
    ]);
    $respDevolver->assertRedirect();

    $compra->refresh();
    expect($compra->estado)->toBe(EstadoCompra::DEVUELTA);

    // Comprobante de devolución registrado
    $devolucion = DevolucionCompra::where('compra_id', $compra->id)->first();
    expect($devolucion)->not->toBeNull()
        ->and($devolucion->motivo)->toContain('Cadena de frío');

    // Existencias revertidas
    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(5.0);
});
