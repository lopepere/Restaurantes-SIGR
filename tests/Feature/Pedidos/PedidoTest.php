<?php

use App\Actions\Pedidos\ActualizarEstadoPedidoAction;
use App\Actions\Pedidos\CrearPedidoAction;
use App\Actions\Pedidos\EditarPedidoAction;
use App\Actions\Pedidos\RepetirPedidoAction;
use App\Actions\Pedidos\ResolverCancelacionAction;
use App\Actions\Pedidos\SolicitarCancelacionAction;
use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Enums\OrigenUsuario;
use App\Enums\PropinaTipo;
use App\Enums\TipoImpuesto;
use App\Enums\TipoPedido;
use App\Events\CancelacionSolicitada;
use App\Events\PedidoActualizado;
use App\Events\PedidoCreado;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\Espacio;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use App\Notifications\InvitacionClienteNotification;
use App\Notifications\PedidoConfirmadoNotification;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Pedidos Test',
        'slug' => 'sede-pedidos-test',
        'direccion' => 'Carrera 15 # 85-30',
        'telefono' => '+573009998877',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->categoria = Categoria::withoutGlobalScopes()
        ->where('sucursal_id', $this->sucursal->id)
        ->first();

    $this->mesa = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 1',
        'tipo' => 'mesa',
        'capacidad' => 4,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    $this->admin = User::create([
        'nombre' => 'Admin Pedidos',
        'email' => 'admin.pedidos@sigr.test',
        'telefono' => '+573001112233',
        'direccion' => 'Calle 100',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->mesero = User::create([
        'nombre' => 'Mesero Pedidos',
        'email' => 'mesero.pedidos@sigr.test',
        'telefono' => '+573002223344',
        'direccion' => 'Calle 101',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->mesero->assignRole('mesero');
    $this->mesero->sucursales()->attach($this->sucursal->id);

    $this->cliente = User::create([
        'nombre' => 'Cliente Registrado',
        'email' => 'cliente.registrado@sigr.test',
        'telefono' => '+573003334455',
        'direccion' => 'Calle 102 # 5-10',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
        'origen' => OrigenUsuario::REGISTRO,
    ]);
    $this->cliente->assignRole('cliente');

    // Ítem con control de inventario
    $this->platoConInventario = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Churrasco 300g',
        'slug' => 'churrasco-300g',
        'precio' => 40000.00,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8.00,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 10.00,
        'existencia_minima' => 2.00,
        'disponible' => true,
        'activo' => true,
    ]);
});

test('crear pedido descuenta inventario de forma atomica con lock (CA-27, RF-INV-02)', function () {
    Event::fake([PedidoCreado::class]);
    Notification::fake();

    /** @var CrearPedidoAction $action */
    $action = app(CrearPedidoAction::class);

    $pedido = $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            [
                'item_menu_id' => $this->platoConInventario->id,
                'cantidad' => 3,
                'notas' => 'Término medio',
            ],
        ],
    ]);

    expect($pedido)->toBeInstanceOf(Pedido::class);
    expect($pedido->items)->toHaveCount(1);
    expect((float) $pedido->subtotal)->toBe(120000.00);

    // Existencia previa era 10, ahora debe ser 7
    $platoActualizado = $this->platoConInventario->fresh();
    expect((float) $platoActualizado->existencia)->toBe(7.00);

    Event::assertDispatched(PedidoCreado::class);
});

test('crear pedido rechaza compra si no hay existencias suficientes (CA-27)', function () {
    /** @var CrearPedidoAction $action */
    $action = app(CrearPedidoAction::class);

    expect(fn () => $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            [
                'item_menu_id' => $this->platoConInventario->id,
                'cantidad' => 15, // Superior a la existencia de 10
            ],
        ],
    ]))->toThrow(ValidationException::class);

    // Existencia permanece inalterada
    expect((float) $this->platoConInventario->fresh()->existencia)->toBe(10.00);
});

test('crear pedido de un combo descuenta inventario de sus componentes individuales', function () {
    $ingredienteA = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Hamburguesa Base',
        'slug' => 'hamburguesa-base',
        'precio' => 20000.00,
        'controla_inventario' => true,
        'existencia' => 20.00,
        'disponible' => true,
        'activo' => true,
    ]);

    $ingredienteB = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Gaseosa 400ml',
        'slug' => 'gaseosa-400ml',
        'precio' => 6000.00,
        'controla_inventario' => true,
        'existencia' => 30.00,
        'disponible' => true,
        'activo' => true,
    ]);

    $combo = ItemMenu::create([
        'sucursal_id' => $this->sucursal->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Combo Hamburguesa',
        'slug' => 'combo-hamburguesa',
        'precio' => 23000.00,
        'es_combo' => true,
        'controla_inventario' => false,
        'disponible' => true,
        'activo' => true,
    ]);

    ComboItem::create(['combo_id' => $combo->id, 'item_id' => $ingredienteA->id, 'cantidad' => 1]);
    ComboItem::create(['combo_id' => $combo->id, 'item_id' => $ingredienteB->id, 'cantidad' => 2]);

    /** @var CrearPedidoAction $action */
    $action = app(CrearPedidoAction::class);

    $pedido = $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            [
                'item_menu_id' => $combo->id,
                'cantidad' => 2, // 2 combos = 2 hamburguesas base y 4 gaseosas
            ],
        ],
    ]);

    expect((float) $ingredienteA->fresh()->existencia)->toBe(18.00);
    expect((float) $ingredienteB->fresh()->existencia)->toBe(26.00);
});

test('cliente sin registrar puede pedir y recibe cuenta implícita con invitación en cola (CA-01, CA-33)', function () {
    Notification::fake();

    /** @var CrearPedidoAction $action */
    $action = app(CrearPedidoAction::class);

    $pedido = $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::DOMICILIO,
        'nombre_contacto' => 'Carlos Invitado',
        'email_contacto' => 'carlos.invitado@sigr.test',
        'telefono_contacto' => '3007778899',
        'direccion_entrega' => 'Carrera 7 # 72-10',
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    expect($pedido->cliente_id)->not->toBeNull();

    $usuarioCreado = User::find($pedido->cliente_id);
    expect($usuarioCreado)->not->toBeNull();
    expect($usuarioCreado->email)->toBe('carlos.invitado@sigr.test');
    expect($usuarioCreado->origen)->toBe(OrigenUsuario::PEDIDO);
    expect($usuarioCreado->password)->toBeNull();
    expect($usuarioCreado->invitacion_enviada_at)->not->toBeNull();

    Notification::assertSentTo($usuarioCreado, InvitacionClienteNotification::class);
    Notification::assertSentTo($usuarioCreado, PedidoConfirmadoNotification::class);
});

test('pedido de invitado con cuenta previa no activada reenvía la invitación sin duplicar registro (CA-37)', function () {
    Notification::fake();

    $usuarioInactivo = User::create([
        'nombre' => 'Ana Previa',
        'email' => 'ana.previa@sigr.test',
        'telefono' => '+573008889900',
        'direccion' => 'Calle 50',
        'password' => null,
        'invitacion_token' => 'token-previo-123',
        'origen' => OrigenUsuario::PEDIDO,
    ]);
    $usuarioInactivo->assignRole('cliente');

    /** @var CrearPedidoAction $action */
    $action = app(CrearPedidoAction::class);

    $pedido = $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::DOMICILIO,
        'nombre_contacto' => 'Ana Previa',
        'email_contacto' => 'ana.previa@sigr.test',
        'telefono_contacto' => '3008889900',
        'direccion_entrega' => 'Calle 50',
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    expect($pedido->cliente_id)->toBe($usuarioInactivo->id);
    expect(User::where('email', 'ana.previa@sigr.test')->count())->toBe(1);

    Notification::assertSentTo($usuarioInactivo, InvitacionClienteNotification::class);
});

test('maquina de estados valida transiciones permitidas y rechaza transiciones invalidas (ERS 8.3)', function () {
    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    /** @var ActualizarEstadoPedidoAction $actualizarAction */
    $actualizarAction = app(ActualizarEstadoPedidoAction::class);

    // PENDIENTE -> CONFIRMADO: Válido
    $pedido = $actualizarAction->handle($pedido, EstadoPedido::CONFIRMADO);
    expect($pedido->estado)->toBe(EstadoPedido::CONFIRMADO);

    // CONFIRMADO -> EN_PREPARACION: Válido
    $pedido = $actualizarAction->handle($pedido, EstadoPedido::EN_PREPARACION);
    expect($pedido->estado)->toBe(EstadoPedido::EN_PREPARACION);

    // EN_PREPARACION -> ENTREGADO: Inválido (debe pasar primero por LISTO según ERS 8.3)
    expect(fn () => $actualizarAction->handle($pedido, EstadoPedido::ENTREGADO))
        ->toThrow(ValidationException::class);

    // EN_PREPARACION -> LISTO -> ENTREGADO: Válido
    $pedido = $actualizarAction->handle($pedido, EstadoPedido::LISTO);
    $pedido = $actualizarAction->handle($pedido, EstadoPedido::ENTREGADO);
    expect($pedido->estado)->toBe(EstadoPedido::ENTREGADO);
    expect($pedido->etiquetaCliente())->toBe('Completado');
});

test('ventana de edicion de 5 minutos permite notas al cliente y rechaza edicion de productos (CA-10)', function () {
    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    /** @var EditarPedidoAction $editarAction */
    $editarAction = app(EditarPedidoAction::class);

    // Cliente editando notas dentro de ventana: Válido
    $pedido = $editarAction->handle($pedido, $this->cliente, [
        'notas' => 'Sin sal y bien cocido',
    ]);
    expect($pedido->notas)->toBe('Sin sal y bien cocido');

    // Cliente intentando modificar ítems: Rechazado
    expect(fn () => $editarAction->handle($pedido, $this->cliente, [
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 2],
        ],
    ]))->toThrow(ValidationException::class);

    // Fuera de la ventana de 5 minutos: Rechazado
    $pedido->created_at = now()->subMinutes(6);
    $pedido->save();

    expect(fn () => $editarAction->handle($pedido->fresh(), $this->cliente, [
        'notas' => 'Cambio tardío',
    ]))->toThrow(ValidationException::class);
});

test('solicitar y resolver cancelacion repone el inventario descontado (CA-08, CA-09, CA-27)', function () {
    Event::fake([CancelacionSolicitada::class, PedidoActualizado::class]);

    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 4],
        ],
    ]);

    // Existencia pasó de 10 a 6
    expect((float) $this->platoConInventario->fresh()->existencia)->toBe(6.00);

    // 1. Cliente solicita cancelación (CA-08)
    /** @var SolicitarCancelacionAction $solicitarAction */
    $solicitarAction = app(SolicitarCancelacionAction::class);
    $pedido = $solicitarAction->handle($pedido, 'Se presentó una urgencia familiar', $this->cliente->id);

    expect($pedido->estado)->toBe(EstadoPedido::CANCELACION_SOLICITADA);
    expect($pedido->solicito_cancelacion)->toBeTrue();
    Event::assertDispatched(CancelacionSolicitada::class);

    // 2. Admin aprueba la cancelación (CA-09)
    /** @var ResolverCancelacionAction $resolverAction */
    $resolverAction = app(ResolverCancelacionAction::class);
    $pedido = $resolverAction->handle($pedido, true, 'Autorizado por recepción', $this->admin);

    expect($pedido->estado)->toBe(EstadoPedido::CANCELADO);
    expect($pedido->solicito_cancelacion)->toBeFalse();

    // Existencia repuesta: de 6 regresa a 10 (CA-27)
    expect((float) $this->platoConInventario->fresh()->existencia)->toBe(10.00);
});

test('observer de pedido actualiza estado de la mesa a ocupado y la libera al cancelarse (PedidoObserver)', function () {
    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    expect($this->mesa->fresh()->estado)->toBe(EstadoEspacio::LIBRE);

    // Al crearse el pedido con mesa asignada, pasa a OCUPADO
    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'mesero_id' => $this->mesero->id,
        'espacio_id' => $this->mesa->id,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    expect($this->mesa->fresh()->estado)->toBe(EstadoEspacio::OCUPADO);

    // Al cancelarse el pedido, la mesa vuelve a estar LIBRE
    /** @var ActualizarEstadoPedidoAction $actualizarAction */
    $actualizarAction = app(ActualizarEstadoPedidoAction::class);
    $actualizarAction->handle($pedido, EstadoPedido::CANCELADO, 'Cancelación de prueba');

    expect($this->mesa->fresh()->estado)->toBe(EstadoEspacio::LIBRE);
});

test('calculo de propina voluntaria 10% sobre la base imponible sin impuestos (SC-02)', function () {
    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    // Plato $40.000 con INC 8% -> Base = 40.000 / 1.08 = 37.037,04 -> 37.037,04 * 10% = 3.703,70
    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'propina_tipo' => PropinaTipo::PORCENTAJE,
        'propina_porcentaje' => 10,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 1],
        ],
    ]);

    expect((float) $pedido->propina)->toBe(3703.70);
    expect((float) $pedido->total)->toBe(43703.70);
});

test('repetir pedido evalua disponibilidad e informa faltantes (RF-PED-18)', function () {
    /** @var CrearPedidoAction $crearAction */
    $crearAction = app(CrearPedidoAction::class);

    $pedido = $crearAction->handle([
        'sucursal_id' => $this->sucursal->id,
        'tipo' => TipoPedido::EN_SITIO,
        'cliente_id' => $this->cliente->id,
        'items' => [
            ['item_menu_id' => $this->platoConInventario->id, 'cantidad' => 2],
        ],
    ]);

    /** @var RepetirPedidoAction $repetirAction */
    $repetirAction = app(RepetirPedidoAction::class);

    $resultado = $repetirAction->handle($pedido);
    expect($resultado['puede_repetir_completo'])->toBeTrue();
    expect($resultado['items_disponibles'])->toHaveCount(1);
    expect($resultado['items_no_disponibles'])->toBeEmpty();

    // Si agotamos el ítem
    $this->platoConInventario->existencia = 0;
    $this->platoConInventario->save();

    $resultadoAgotado = $repetirAction->handle($pedido);
    expect($resultadoAgotado['puede_repetir_completo'])->toBeFalse();
    expect($resultadoAgotado['items_no_disponibles'])->toHaveCount(1);
});
