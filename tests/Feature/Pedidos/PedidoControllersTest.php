<?php

use App\Enums\EstadoPedido;
use App\Enums\TipoImpuesto;
use App\Enums\TipoPedido;
use App\Models\Categoria;
use App\Models\Espacio;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede HTTP Test',
        'slug' => 'sede-http-test',
        'direccion' => 'Calle 50 # 10-20',
        'telefono' => '+573001112299',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursal->id)
        ->first();

    $this->plato = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Ajiaco Santafereño',
        'slug' => 'ajiaco-santafereno',
        'precio' => 28000.00,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8.00,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 15.00,
        'disponible' => true,
        'activo' => true,
    ]);

    $this->mesa = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 5',
        'tipo' => 'mesa',
        'capacidad' => 4,
        'activo' => true,
    ]);

    $this->admin = User::create([
        'nombre' => 'Admin Controller',
        'email' => 'admin.ctrl@sigr.test',
        'telefono' => '+573001239999',
        'direccion' => 'Calle 1',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->mesero = User::create([
        'nombre' => 'Mesero Controller',
        'email' => 'mesero.ctrl@sigr.test',
        'telefono' => '+573001238888',
        'direccion' => 'Calle 2',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->mesero->assignRole('mesero');
    $this->mesero->sucursales()->attach($this->sucursal->id);

    $this->cliente = User::create([
        'nombre' => 'Cliente Controller',
        'email' => 'cliente.ctrl@sigr.test',
        'telefono' => '+573001237777',
        'direccion' => 'Calle 3 # 4-5',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->cliente->assignRole('cliente');
});

test('flujo publico: crear pedido desde checkout y ver confirmacion', function () {
    $response = $this->post(route('pedidos.publico.store'), [
        'sucursal_id' => $this->sucursal->id,
        'tipo' => 'domicilio',
        'nombre_contacto' => 'Visitante Web',
        'email_contacto' => 'visitante.web@sigr.test',
        'telefono_contacto' => '3001114455',
        'direccion_entrega' => 'Diagonal 45 # 20-30',
        'notas' => 'Timbre 201',
        'propina_tipo' => 'porcentaje',
        'propina_porcentaje' => 10,
        'items' => [
            [
                'item_menu_id' => $this->plato->id,
                'cantidad' => 2,
                'notas' => 'Bien caliente',
            ],
        ],
    ]);

    $pedido = Pedido::withoutGlobalScopes()->where('direccion_entrega', 'Diagonal 45 # 20-30')->first();
    expect($pedido)->not->toBeNull();

    $response->assertRedirect(route('pedidos.publico.confirmado', ['numero' => $pedido->numero]));

    $responseConfirmacion = $this->get(route('pedidos.publico.confirmado', ['numero' => $pedido->numero]));
    $responseConfirmacion->assertOk();
});

test('flujo cliente: ver pedidos, editar notas dentro de 5 min y solicitar cancelacion', function () {
    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'numero' => 'PED-TEST-0001',
        'cliente_id' => $this->cliente->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'total' => 28000.00,
        'subtotal' => 28000.00,
    ]);

    // 1. Listado y detalle
    $this->actingAs($this->cliente)
        ->get(route('cliente.pedidos.index'))
        ->assertOk();

    $this->actingAs($this->cliente)
        ->get(route('cliente.pedidos.show', $pedido))
        ->assertOk();

    // 2. Editar notas dentro de los 5 min (CA-10)
    $this->actingAs($this->cliente)
        ->put(route('cliente.pedidos.notas.update', $pedido), [
            'notas' => 'Por favor agregar ají casero',
        ])
        ->assertRedirect();

    expect($pedido->fresh()->notas)->toBe('Por favor agregar ají casero');

    // 3. Solicitar cancelación (CA-08)
    $this->actingAs($this->cliente)
        ->post(route('cliente.pedidos.cancelar', $pedido), [
            'motivo' => 'Tengo que retirarme por una urgencia',
        ])
        ->assertRedirect();

    expect($pedido->fresh()->solicito_cancelacion)->toBeTrue();
    expect($pedido->fresh()->estado)->toBe(EstadoPedido::CANCELACION_SOLICITADA);
});

test('flujo mesero: crear comanda para mesa en sala', function () {
    $response = $this->actingAs($this->mesero)
        ->post(route('mesero.pedidos.store'), [
            'tipo' => 'en_sitio',
            'espacio_id' => $this->mesa->id,
            'nombre_contacto' => 'Mesa Ejecutiva',
            'notas' => 'Entregar platos juntos',
            'items' => [
                [
                    'item_menu_id' => $this->plato->id,
                    'cantidad' => 1,
                ],
            ],
        ]);

    $response->assertRedirect(route('mesero.pedidos.index'));

    $pedido = Pedido::withoutGlobalScopes()->where('mesero_id', $this->mesero->id)->first();
    expect($pedido)->not->toBeNull();
    expect($pedido->espacio_id)->toBe($this->mesa->id);
});

test('flujo admin: monitor en tiempo real, cambio de estado y resolucion de cancelacion', function () {
    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'numero' => 'PED-ADMIN-0002',
        'cliente_id' => $this->cliente->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'total' => 28000.00,
        'subtotal' => 28000.00,
    ]);

    // 1. Ver monitor de pedidos
    $this->actingAs($this->admin)
        ->get(route('admin.pedidos.index'))
        ->assertOk();

    // 2. Avanzar estado a CONFIRMADO (ERS 8.3)
    $this->actingAs($this->admin)
        ->post(route('admin.pedidos.estado', $pedido), [
            'nuevo_estado' => 'confirmado',
            'motivo' => 'Confirmado por recepción',
        ])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(EstadoPedido::CONFIRMADO);

    // 3. Cliente solicita cancelación y admin resuelve aprobando (CA-09)
    $pedido->estado = EstadoPedido::CANCELACION_SOLICITADA;
    $pedido->solicito_cancelacion = true;
    $pedido->motivo_cancelacion = 'Cancelación solicitada por demora';
    $pedido->save();

    $this->actingAs($this->admin)
        ->post(route('admin.pedidos.resolver-cancelacion', $pedido), [
            'aprobar' => true,
            'respuesta_admin' => 'Aprobado y disculpas por el retraso',
        ])
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(EstadoPedido::CANCELADO);
    expect($pedido->fresh()->solicito_cancelacion)->toBeFalse();
});
