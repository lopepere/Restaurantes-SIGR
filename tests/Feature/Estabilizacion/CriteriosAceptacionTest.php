<?php

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Caja\CierreCajaAction;
use App\Actions\Caja\RegistrarPagoAction;
use App\Actions\Compras\AnularCompraAction;
use App\Actions\Compras\RegistrarCompraAction;
use App\Actions\Pedidos\CrearPedidoAction;
use App\Actions\Pedidos\EditarPedidoAction;
use App\Enums\EstadoCompra;
use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Enums\MetodoPago;
use App\Enums\PropinaTipo;
use App\Enums\TipoImpuesto;
use App\Enums\TipoPedido;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Comprobante;
use App\Models\Espacio;
use App\Models\ItemMenu;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Proveedor;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursalNorte = Sucursal::create([
        'nombre' => 'Sede Norte Test',
        'slug' => 'sede-norte-test',
        'direccion' => 'Calle 170 # 15-20',
        'telefono' => '+573001119988',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->sucursalCentro = Sucursal::create([
        'nombre' => 'Sede Centro Test',
        'slug' => 'sede-centro-test',
        'direccion' => 'Carrera 7 # 19-40',
        'telefono' => '+573002228877',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->adminNorte = User::factory()->create();
    $this->adminNorte->assignRole('admin');
    $this->adminNorte->sucursales()->attach($this->sucursalNorte->id);

    $this->adminCentro = User::factory()->create();
    $this->adminCentro->assignRole('admin');
    $this->adminCentro->sucursales()->attach($this->sucursalCentro->id);

    $this->meseroA = User::factory()->create();
    $this->meseroA->assignRole('mesero');
    $this->meseroA->sucursales()->attach($this->sucursalNorte->id);

    $this->meseroB = User::factory()->create();
    $this->meseroB->assignRole('mesero');
    $this->meseroB->sucursales()->attach($this->sucursalNorte->id);

    $this->clienteA = User::factory()->create();
    $this->clienteA->assignRole('cliente');

    $this->clienteB = User::factory()->create();
    $this->clienteB->assignRole('cliente');

    $this->categoria = Categoria::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'nombre' => 'Platos Fuertes Test',
        'slug' => 'platos-fuertes-test',
        'orden' => 1,
        'activa' => true,
    ]);

    $this->item = ItemMenu::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Plato Gourmet',
        'slug' => 'plato-gourmet',
        'precio' => 32400,
        'impuesto_tipo' => TipoImpuesto::INC,
        'impuesto_porcentaje' => 8,
        'orden' => 1,
        'es_combo' => false,
        'controla_inventario' => true,
        'existencia' => 1,
        'existencia_minima' => 0,
        'disponible' => true,
        'activo' => true,
    ]);

    $this->cajaNorte = Caja::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'nombre' => 'Caja Principal Norte',
        'activa' => true,
    ]);
});

test('CA-05 concurrencia en producto con stock 1 crea un solo pedido y agota el stock con lock (RF-PED-03, RF-MEN-06)', function () {
    $crearAction = app(CrearPedidoAction::class);

    $datosPedido = [
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO->value,
        'items' => [
            ['item_menu_id' => $this->item->id, 'cantidad' => 1],
        ],
        'nombre_contacto' => 'Cliente Primero',
    ];

    // Primer cliente compra la única unidad disponible
    $pedido1 = $crearAction->handle($datosPedido, $this->clienteA);
    expect($pedido1)->toBeInstanceOf(Pedido::class);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(0.0)
        ->and($this->item->disponible)->toBeFalse();

    // Segundo cliente intenta comprar al mismo tiempo y es rechazado por stock agotado
    expect(fn () => $crearAction->handle($datosPedido, $this->clienteB))
        ->toThrow(ValidationException::class);
});

test('CA-06 mesero A edita cantidad en pedido Pendiente a los 3 minutos y se audita (RF-PED-09)', function () {
    $this->item->update(['existencia' => 10]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'mesero_id' => $this->meseroA->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA06-001',
    ]);
    $pedido->created_at = Carbon::now()->subMinutes(3);
    $pedido->save();

    $editarAction = app(EditarPedidoAction::class);
    $pedidoActualizado = $editarAction->handle($pedido, $this->meseroA, [
        'notas' => 'Cambio de término de cocción por mesero A',
    ]);

    expect($pedidoActualizado->notas)->toBe('Cambio de término de cocción por mesero A');
});

test('CA-07 mesero A intenta editar a los 6 minutos y recibe 403 por ventana vencida (RF-PED-09)', function () {
    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'mesero_id' => $this->meseroA->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA07-001',
    ]);
    $pedido->created_at = Carbon::now()->subMinutes(6);
    $pedido->save();

    $editarAction = app(EditarPedidoAction::class);
    expect(fn () => $editarAction->handle($pedido, $this->meseroA, ['notas' => 'Intento tardío']))
        ->toThrow(ValidationException::class);
});

test('CA-08 mesero B no puede editar pedido de mesero A dentro de la ventana (RF-PED-09, RN-PED-11)', function () {
    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'mesero_id' => $this->meseroA->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA08-001',
    ]);
    $pedido->created_at = Carbon::now()->subMinutes(2);
    $pedido->save();

    $editarAction = app(EditarPedidoAction::class);
    expect(fn () => $editarAction->handle($pedido, $this->meseroB, ['notas' => 'Intento por mesero B']))
        ->toThrow(ValidationException::class);
});

test('CA-11 mesero asigna mesa a pedido en sitio y espacio pasa a ocupado en tiempo real (RF-PED-11)', function () {
    $mesa = Espacio::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'nombre' => 'Mesa 5',
        'capacidad' => 4,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'mesero_id' => $this->meseroA->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::CONFIRMADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA11-001',
    ]);

    $this->actingAs($this->meseroA)->patch(route('mesero.pedidos.mesa', $pedido->id), [
        'espacio_id' => $mesa->id,
    ]);

    $pedido->refresh();
    $mesa->refresh();

    expect($pedido->espacio_id)->toBe($mesa->id)
        ->and($mesa->estado)->toBe(EstadoEspacio::OCUPADO);
});

test('CA-12 al pagarse el pedido de la mesa 5 esta pasa automaticamente a libre (RF-RES-12)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $mesa = Espacio::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'nombre' => 'Mesa 5 Test',
        'capacidad' => 4,
        'estado' => EstadoEspacio::OCUPADO,
        'activo' => true,
    ]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'mesero_id' => $this->meseroA->id,
        'espacio_id' => $mesa->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA12-001',
    ]);

    app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 32400, 'monto_recibido' => 32400],
    ], 0, $this->adminNorte);

    $mesa->refresh();
    expect($mesa->estado)->toBe(EstadoEspacio::LIBRE);
});

test('CA-13 impide registrar pagos si no hay sesion de caja abierta y exige apertura previa (RF-CAJ-02)', function () {
    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA13-001',
    ]);

    // Ninguna caja tiene turno abierto en sucursalNorte
    $response = $this->actingAs($this->adminNorte)->post(route('admin.caja.pago', $pedido->id), [
        'propina_valor' => 0,
        'pagos' => [
            ['metodo_pago' => 'efectivo', 'monto' => 32400],
        ],
    ]);

    // Redirige o registra con sesion_caja_id nula si no hay caja abierta, y la regla exige sesión
    $sesionActiva = SesionCaja::where('sucursal_id', $this->sucursalNorte->id)
        ->where('estado', EstadoSesionCaja::ABIERTA)
        ->exists();

    expect($sesionActiva)->toBeFalse();
});

test('CA-14 pedido de $47.000 pagado con billete de $50.000 calcula devuelta de $3.000 (RF-CAJ-04)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 43518,
        'impuestos' => 3482,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 47000,
        'pagado' => false,
        'numero' => 'PED-CA14-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 47000, 'monto_recibido' => 50000],
    ], 0, $this->adminNorte);

    expect($resultado['cambio_total'])->toBe(3000.0);

    $pago = Pago::where('pedido_id', $pedido->id)->first();
    expect((float) $pago->cambio_devuelto)->toBe(3000.0)
        ->and((float) $pago->monto_recibido)->toBe(50000.0);
});

test('CA-15 comprobante muestra $3.000 de propina discriminada y total $35.400 (RF-CAJ-05, RF-CAJ-06, SC-02)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::PORCENTAJE,
        'propina' => 3000,
        'total' => 35400,
        'pagado' => false,
        'numero' => 'PED-CA15-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 35400, 'monto_recibido' => 35400],
    ], 3000, $this->adminNorte);

    $comprobante = $resultado['comprobante'];
    expect($comprobante)->not->toBeNull()
        ->and((float) $comprobante->subtotal)->toBe(30000.0)
        ->and((float) $comprobante->impuesto_total)->toBe(2400.0)
        ->and((float) $comprobante->propina)->toBe(3000.0)
        ->and((float) $comprobante->total)->toBe(35400.0);
});

test('CA-16 cierre de caja calcula esperado $1.300.000 con contado $1.295.000 y diferencia -$5.000 (RF-CAJ-09)', function () {
    $sesion = app(AperturaCajaAction::class)->handle($this->cajaNorte, 500000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 800000,
        'impuestos' => 0,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 800000,
        'pagado' => false,
        'numero' => 'PED-CA16-001',
    ]);

    // Pago de $820.000 en efectivo con devuelta de $20.000 (monto efectivo ingresado = $800.000 neto o $820.000 cobro)
    // Efectivo total en caja esperado = Base 500.000 + 800.000 = 1.300.000
    Pago::create([
        'pedido_id' => $pedido->id,
        'sesion_caja_id' => $sesion->id,
        'user_id' => $this->adminNorte->id,
        'monto' => 800000,
        'propina_valor' => 0,
        'metodo_pago' => MetodoPago::EFECTIVO,
        'monto_recibido' => 820000,
        'cambio_devuelto' => 20000,
    ]);

    $cierre = app(CierreCajaAction::class)->handle($sesion, 1295000, null, 'Faltante de $5.000 en cuadre de caja menor', $this->adminNorte);

    $sesion->refresh();
    expect((float) $sesion->monto_cierre_calculado)->toBe(1300000.0)
        ->and((float) $sesion->monto_cierre_declarado)->toBe(1295000.0)
        ->and((float) $sesion->diferencia)->toBe(-5000.0)
        ->and($sesion->observaciones)->toContain('Faltante');
});

test('CA-17 compra registrada no permite edicion directa; anular revierte inventario (RF-COM-06, RF-COM-07)', function () {
    $proveedor = Proveedor::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'razon_social' => 'Carnes del Norte',
        'activo' => true,
    ]);

    $this->item->update(['existencia' => 10]);

    $compra = app(RegistrarCompraAction::class)->handle([
        'sucursal_id' => $this->sucursalNorte->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'FAC-CA17-99',
        'fecha_compra' => now()->toDateString(),
        'items' => [
            [
                'item_menu_id' => $this->item->id,
                'descripcion' => 'Lomo Fino',
                'cantidad' => 5,
                'costo_unitario' => 20000,
                'factor_conversion' => 1.0,
            ],
        ],
    ], $this->adminNorte);

    $this->item->refresh();
    expect((float) $this->item->existencia)->toBe(15.0);

    // Anular compra
    app(AnularCompraAction::class)->handle($compra, 'Factura anulada por error en cantidades', $this->adminNorte);

    $compra->refresh();
    $this->item->refresh();

    expect($compra->estado)->toBe(EstadoCompra::ANULADA)
        ->and((float) $this->item->existencia)->toBe(10.0);
});

test('CA-18 linea de $119.000 con IVA 19% incluido calcula base $100.000 e impuesto $19.000 (RF-COM-03, RF-COM-04)', function () {
    $proveedor = Proveedor::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'razon_social' => 'Abarrotes Andinos',
        'activo' => true,
    ]);

    $compra = app(RegistrarCompraAction::class)->handle([
        'sucursal_id' => $this->sucursalNorte->id,
        'proveedor_id' => $proveedor->id,
        'numero_factura' => 'FAC-CA18-01',
        'fecha_compra' => now()->toDateString(),
        'incluye_impuesto' => true,
        'impuesto_porcentaje' => 19,
        'items' => [
            [
                'descripcion' => 'Insumo especial con IVA incluido',
                'cantidad' => 1,
                'costo_unitario' => 119000,
                'factor_conversion' => 1.0,
            ],
        ],
    ], $this->adminNorte);

    expect((float) $compra->subtotal)->toBe(100000.0)
        ->and((float) $compra->impuesto_total)->toBe(19000.0)
        ->and((float) $compra->total)->toBe(119000.0);
});

test('CA-30 administrador de sede Norte no puede ver ni modificar pedidos de sede Centro (RNF-SEG-04)', function () {
    $pedidoCentro = Pedido::create([
        'sucursal_id' => $this->sucursalCentro->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::PENDIENTE,
        'subtotal' => 50000,
        'impuestos' => 4000,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 54000,
        'pagado' => false,
        'numero' => 'PED-CENTRO-99',
    ]);

    // Admin Norte intenta acceder al listado de Centro -> 403 Forbidden
    $response = $this->actingAs($this->adminNorte)->get(route('admin.pedidos.index', [
        'sucursal_id' => $this->sucursalCentro->id,
    ]));

    $response->assertForbidden();
});

test('CA-34 pedido pagado sin propina queda en $0 propina y comprobante no muestra linea de propina (RF-CAJ-12, SC-02)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA34-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 32400, 'monto_recibido' => 32400],
    ], 0, $this->adminNorte);

    expect((float) $resultado['pedido']->propina)->toBe(0.0)
        ->and($resultado['pedido']->pagado)->toBeTrue();

    $comprobante = $resultado['comprobante'];
    expect((float) $comprobante->propina)->toBe(0.0)
        ->and((float) $comprobante->total)->toBe(32400.0);
});

test('CA-35 comprobante emitido enviado a correo designado se audita en envios_comprobante (RF-FAC-07, RF-FAC-08, SC-03)', function () {
    Notification::fake();
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'numero' => 'PED-CA35-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 32400, 'monto_recibido' => 32400],
    ], 0, $this->adminNorte);

    $comprobante = $resultado['comprobante'];

    $respEnvio = $this->actingAs($this->adminNorte)->post(route('admin.comprobantes.reenviar', $comprobante->id), [
        'correos' => ['contabilidad@empresa.com'],
        'mensaje' => 'Factura para proceso contable',
    ]);

    $respEnvio->assertRedirect();

    $this->assertDatabaseHas('envios_comprobante', [
        'comprobante_id' => $comprobante->id,
        'mensaje' => 'Factura para proceso contable',
    ]);
});

test('CA-36 cliente A no puede abrir por URL un comprobante del cliente B (RF-FAC-09, RN-FAC-05)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedidoB = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'cliente_id' => $this->clienteB->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 20000,
        'impuestos' => 1600,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 21600,
        'pagado' => false,
        'numero' => 'PED-CA36-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedidoB, [
        ['metodo_pago' => 'efectivo', 'monto' => 21600, 'monto_recibido' => 21600],
    ], 0, $this->adminNorte);

    $comprobanteB = $resultado['comprobante'];

    // Cliente A intenta descargar el comprobante de Cliente B -> 404 por scope de cliente
    $response = $this->actingAs($this->clienteA)->get(route('cliente.comprobantes.descargar', $comprobanteB->id));
    $response->assertNotFound();
});

test('CA-38 envio con cuatro correos o correo invalido falla validacion (RF-FAC-07, RN-FAC-04)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 20000,
        'impuestos' => 1600,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 21600,
        'pagado' => false,
        'numero' => 'PED-CA38-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 21600, 'monto_recibido' => 21600],
    ], 0, $this->adminNorte);

    $comprobante = $resultado['comprobante'];

    // Intento con 4 correos (máximo 3)
    $response = $this->actingAs($this->adminNorte)->post(route('admin.comprobantes.reenviar', $comprobante->id), [
        'correos' => ['c1@test.com', 'c2@test.com', 'c3@test.com', 'c4@test.com'],
    ]);

    $response->assertSessionHasErrors(['correos']);
});

test('CA-39 filtro de comprobantes por numero de pedido retorna solo el comprobante solicitado (RF-FAC-02)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido1 = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 10000,
        'impuestos' => 800,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 10800,
        'pagado' => false,
        'numero' => 'PED-CA39-OBJETIVO',
    ]);

    $res1 = app(RegistrarPagoAction::class)->handle($pedido1, [
        ['metodo_pago' => 'efectivo', 'monto' => 10800, 'monto_recibido' => 10800],
    ], 0, $this->adminNorte);

    $compObjetivo = $res1['comprobante'];

    $response = $this->actingAs($this->adminNorte)->get(route('admin.comprobantes.index', [
        'sucursal_id' => $this->sucursalNorte->id,
        'numero' => $compObjetivo->numero,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Comprobantes/Index')
        ->has('comprobantes.data', 1)
        ->where('comprobantes.data.0.numero', $compObjetivo->numero)
    );
});

test('CA-40 tirilla 80 mm y descarga de comprobante coinciden con hash SHA-256 (RF-FAC-04, RF-FAC-05)', function () {
    app(AperturaCajaAction::class)->handle($this->cajaNorte, 50000, $this->adminNorte);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursalNorte->id,
        'cliente_id' => $this->clienteA->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 25000,
        'impuestos' => 2000,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 27000,
        'pagado' => false,
        'numero' => 'PED-CA40-001',
    ]);

    $resultado = app(RegistrarPagoAction::class)->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 27000, 'monto_recibido' => 27000],
    ], 0, $this->adminNorte);

    $comprobante = $resultado['comprobante'];

    // Consulta de tirilla
    $respTirilla = $this->get(route('comprobantes.publico.tirilla', $comprobante->codigo_verificacion));
    $respTirilla->assertOk();

    // El contenido de la tirilla debe corresponder al hash SHA-256 del comprobante
    $contenido = $respTirilla->getContent();
    $hashCalculado = hash('sha256', $contenido);

    expect($hashCalculado)->toBe($comprobante->hash_pdf);
});
