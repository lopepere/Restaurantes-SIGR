<?php

use App\Enums\ModoInventario;
use App\Enums\TipoImpuesto;
use App\Enums\TipoMovimiento;
use App\Models\Categoria;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\InventarioService;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Inventario Test',
        'slug' => 'sede-inventario-test',
        'direccion' => 'Carrera 7 # 72-10',
        'telefono' => '+573001112233',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Bebidas Test',
        'slug' => 'bebidas-test',
        'orden' => 1,
        'activa' => true,
    ]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->item = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Gaseosa Artesanal',
        'slug' => 'gaseosa-artesanal',
        'precio' => 8000,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'orden' => 1,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 0,
        'existencia_minima' => 5,
        'disponible' => true,
        'activo' => true,
    ]);
});

test('puede registrar inventario inicial unico y rechaza segundo registro (RF-INV-01, RF-INV-04)', function () {
    $movimiento = InventarioService::registrarInventarioInicial($this->item, 20, 3500, $this->admin);

    expect($movimiento)->toBeInstanceOf(MovimientoInventario::class)
        ->and($movimiento->tipo)->toBe(TipoMovimiento::INICIAL)
        ->and((float) $movimiento->cantidad)->toBe(20.0)
        ->and((float) $movimiento->saldo_cantidad)->toBe(20.0);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(20.0);

    // Intento de segundo inventario inicial debe lanzar ValidationException
    expect(fn () => InventarioService::registrarInventarioInicial($this->item, 10, 3500, $this->admin))
        ->toThrow(ValidationException::class);
});

test('descuenta existencias por venta y repone por cancelacion manteniendo kardex (RF-INV-02, RF-INV-03, CA-27)', function () {
    InventarioService::registrarInventarioInicial($this->item, 50, 3500, $this->admin);

    // Descontar por venta
    $movVenta = InventarioService::descontar($this->item, 5, null, null, 'Venta pedido #101', $this->admin);
    expect((float) $movVenta->cantidad)->toBe(5.0)
        ->and((float) $movVenta->saldo_cantidad)->toBe(45.0);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(45.0);

    // Reponer por cancelación
    $movReposicion = InventarioService::reponer($this->item, 5, null, null, 'Cancelación pedido #101', $this->admin);
    expect((float) $movReposicion->cantidad)->toBe(5.0)
        ->and((float) $movReposicion->saldo_cantidad)->toBe(50.0);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(50.0);
});

test('permite ajuste manual justificado con motivo obligatorio (RF-INV-05)', function () {
    InventarioService::registrarInventarioInicial($this->item, 30, 3500, $this->admin);

    // Ajuste positivo
    $mov = InventarioService::ajustar($this->item, 35, 'Conteo físico mensual: se encontraron 5 unidades adicionales', $this->admin);
    expect((float) $mov->cantidad)->toBe(5.0)
        ->and($mov->tipo)->toBe(TipoMovimiento::AJUSTE_POSITIVO)
        ->and((float) $mov->saldo_cantidad)->toBe(35.0);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(35.0);

    // Existencia idéntica lanza ValidationException
    expect(fn () => InventarioService::ajustar($this->item, 35, 'Sin cambio', $this->admin))
        ->toThrow(ValidationException::class);
});

test('permite actualizacion de stock por lote con justificacion (RF-INV-06)', function () {
    $item2 = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Cerveza Club Colombia',
        'slug' => 'cerveza-club-colombia',
        'precio' => 9000,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'orden' => 2,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 15,
        'existencia_minima' => 10,
        'disponible' => true,
        'activo' => true,
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.inventario.ajustar-lote'), [
        'sucursal_id' => $this->sucursal->id,
        'motivo' => 'Auditoría trimestral de bodega',
        'items' => [
            ['item_id' => $this->item->id, 'nueva_existencia' => 40],
            ['item_id' => $item2->id, 'nueva_existencia' => 25],
        ],
    ]);

    $response->assertRedirect();
    $this->item->refresh();
    $item2->refresh();

    expect((float) $this->item->existencia)->toBe(40.0)
        ->and((float) $item2->existencia)->toBe(25.0);
});

test('detecta alertas de stock minimo correctamente (RF-INV-08, CA-28)', function () {
    $this->item->update(['existencia' => 3, 'existencia_minima' => 5]);

    $alertas = InventarioService::alertasStockMinimo($this->sucursal->id);

    expect($alertas)->toHaveCount(1)
        ->and($alertas->first()->id)->toBe($this->item->id);
});

test('solo superadmin puede cambiar el modo de operacion de inventario (RF-INV-07)', function () {
    // Admin de sucursal no puede
    $respAdmin = $this->actingAs($this->admin)->post(route('admin.inventario.modo'), [
        'sucursal_id' => $this->sucursal->id,
        'modo' => 'dependiente',
    ]);
    $respAdmin->assertForbidden();

    // SuperAdmin sí puede
    $respSuper = $this->actingAs($this->superAdmin)->post(route('admin.inventario.modo'), [
        'sucursal_id' => $this->sucursal->id,
        'modo' => 'dependiente',
    ]);
    $respSuper->assertRedirect();

    expect(InventarioService::obtenerModo($this->sucursal->id))->toBe(ModoInventario::DEPENDIENTE);
});
