<?php

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Caja\CierreCajaAction;
use App\Actions\Caja\RegistrarPagoAction;
use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Caja Test',
        'slug' => 'sede-caja-test',
        'direccion' => 'Calle 100 # 15-20',
        'telefono' => '+573004445566',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->mesero = User::factory()->create();
    $this->mesero->assignRole('mesero');
    $this->mesero->sucursales()->attach($this->sucursal->id);

    $this->caja = Caja::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Caja Principal Sede',
        'activa' => true,
    ]);
});

test('puede abrir sesion de caja con base inicial en efectivo y rechaza doble apertura (RF-CAJ-02, CA-29)', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.caja.apertura'), [
        'caja_id' => $this->caja->id,
        'monto_apertura' => 100000,
    ]);

    $response->assertRedirect();

    $sesion = SesionCaja::where('caja_id', $this->caja->id)->firstOrFail();
    expect($sesion->estado)->toBe(EstadoSesionCaja::ABIERTA)
        ->and((float) $sesion->monto_apertura)->toBe(100000.0);

    // Intento de segunda apertura sobre la misma caja debe fallar
    expect(fn () => app(AperturaCajaAction::class)->handle($this->caja, 50000, $this->admin))
        ->toThrow(ValidationException::class);
});

test('puede registrar pago simple en efectivo con cambio y propina voluntaria generando comprobante (RF-CAJ-04, CA-30, SC-02)', function () {
    // Abrir turno
    $this->actingAs($this->admin)->post(route('admin.caja.apertura'), [
        'caja_id' => $this->caja->id,
        'monto_apertura' => 50000,
    ]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'mesero_id' => $this->mesero->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 30000,
        'impuestos' => 2400,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 32400,
        'pagado' => false,
        'nombre_contacto' => 'Carlos Cliente',
        'numero' => 'PED-TEST-001',
    ]);

    // Cliente decide dejar $3,000 de propina voluntaria (SC-02) -> Total pedido pasa a $35,400
    // Paga con billete de $50,000 -> Devuelta calculada $14,600
    $response = $this->actingAs($this->admin)->post(route('admin.caja.pago', $pedido->id), [
        'propina_valor' => 3000,
        'pagos' => [
            [
                'metodo_pago' => 'efectivo',
                'monto' => 35400,
                'monto_recibido' => 50000,
            ],
        ],
    ]);

    $response->assertRedirect();

    $pedido->refresh();
    expect($pedido->pagado)->toBeTrue()
        ->and($pedido->estado)->toBe(EstadoPedido::PAGADO)
        ->and((float) $pedido->propina)->toBe(3000.0)
        ->and((float) $pedido->total)->toBe(35400.0);

    // Pago registrado con devuelta
    $pago = Pago::where('pedido_id', $pedido->id)->firstOrFail();
    expect((float) $pago->monto)->toBe(35400.0)
        ->and((float) $pago->monto_recibido)->toBe(50000.0)
        ->and((float) $pago->cambio_devuelto)->toBe(14600.0);

    // Comprobante inmutable generado automáticamente
    $comprobante = Comprobante::where('pedido_id', $pedido->id)->firstOrFail();
    expect($comprobante->numero)->toContain('FAC-')
        ->and((float) $comprobante->total)->toBe(35400.0)
        ->and((float) $comprobante->propina)->toBe(3000.0)
        ->and($comprobante->hash_pdf)->not->toBeEmpty();
});

test('puede registrar pagos mixtos para cubrir un pedido (RF-CAJ-04)', function () {
    // Abrir turno
    $this->actingAs($this->admin)->post(route('admin.caja.apertura'), [
        'caja_id' => $this->caja->id,
        'monto_apertura' => 50000,
    ]);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'mesero_id' => $this->mesero->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 40000,
        'impuestos' => 3200,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::NINGUNA,
        'propina' => 0,
        'total' => 43200,
        'pagado' => false,
        'nombre_contacto' => 'Ana Gómez',
        'numero' => 'PED-TEST-002',
    ]);

    // Pago mixto: $20,000 en Efectivo y $23,200 por Transferencia/Nequi
    $response = $this->actingAs($this->admin)->post(route('admin.caja.pago', $pedido->id), [
        'propina_valor' => 0,
        'pagos' => [
            [
                'metodo_pago' => 'efectivo',
                'monto' => 20000,
                'monto_recibido' => 20000,
            ],
            [
                'metodo_pago' => 'transferencia',
                'monto' => 23200,
                'monto_recibido' => 23200,
                'referencia_transaccion' => 'NEQ-987654321',
            ],
        ],
    ]);

    $response->assertRedirect();

    $pedido->refresh();
    expect($pedido->pagado)->toBeTrue()
        ->and($pedido->estado)->toBe(EstadoPedido::PAGADO);

    $comprobante = Comprobante::where('pedido_id', $pedido->id)->firstOrFail();
    expect($comprobante->metodo_pago)->toBe('mixto')
        ->and((float) $comprobante->total)->toBe(43200.0);
});

test('puede cerrar turno de caja con arqueo, calculando diferencia y propinas por mesero (RF-CAJ-05, CA-30)', function () {
    $aperturaAction = app(AperturaCajaAction::class);
    $sesion = $aperturaAction->handle($this->caja, 50000, $this->admin);

    $pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'mesero_id' => $this->mesero->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 20000,
        'impuestos' => 1600,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::ABIERTA,
        'propina' => 2000,
        'total' => 23600,
        'pagado' => false,
        'nombre_contacto' => 'Juan Pérez',
        'numero' => 'PED-TEST-003',
    ]);

    // Pago de $23,600 en efectivo
    $pagoAction = app(RegistrarPagoAction::class);
    $pagoAction->handle($pedido, [
        ['metodo_pago' => 'efectivo', 'monto' => 23600, 'monto_recibido' => 23600],
    ], 2000, $this->admin);

    // En caja debe haber: $50,000 base + $23,600 cobro = $73,600
    // Realizar arqueo declarando exactamente $73,600 (cuadrada)
    $cierreAction = app(CierreCajaAction::class);
    $resultado = $cierreAction->handle(
        $sesion,
        73600,
        ['billetes_50k' => 1, 'billetes_20k' => 1, 'monedas' => 3600],
        'Arqueo conforme al cierre del turno',
        $this->admin
    );

    $sesion->refresh();
    expect($sesion->estado)->toBe(EstadoSesionCaja::CERRADA)
        ->and((float) $sesion->monto_cierre_calculado)->toBe(73600.0)
        ->and((float) $sesion->monto_cierre_declarado)->toBe(73600.0)
        ->and((float) $sesion->diferencia)->toBe(0.0);

    // Consolidación de propinas
    expect($resultado['propinas_por_mesero'])->toHaveCount(1)
        ->and($resultado['propinas_por_mesero'][0]['mesero_id'])->toBe($this->mesero->id)
        ->and($resultado['propinas_por_mesero'][0]['total_propinas'])->toBe(2000.0);
});
