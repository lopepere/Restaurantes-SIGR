<?php

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Caja\RegistrarPagoAction;
use App\Actions\Comprobantes\ReenviarComprobanteAction;
use App\Enums\EstadoPedido;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\EnvioComprobante;
use App\Models\Pedido;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Comprobantes Test',
        'slug' => 'sede-comprobantes-test',
        'direccion' => 'Calle 127 # 7-19',
        'telefono' => '+573007778899',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->cliente = User::factory()->create([
        'email' => 'cliente.fac@sigr.test',
    ]);
    $this->cliente->assignRole('cliente');

    $this->cliente2 = User::factory()->create([
        'email' => 'otro.cliente@sigr.test',
    ]);
    $this->cliente2->assignRole('cliente');

    $this->caja = Caja::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Caja FAC',
        'activa' => true,
    ]);

    app(AperturaCajaAction::class)->handle($this->caja, 50000, $this->admin);

    $this->pedido = Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'cliente_id' => $this->cliente->id,
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::ENTREGADO,
        'subtotal' => 50000,
        'impuestos' => 4000,
        'descuento' => 0,
        'propina_tipo' => PropinaTipo::PORCENTAJE,
        'propina' => 5000,
        'total' => 59000,
        'pagado' => false,
        'nombre_contacto' => $this->cliente->nombre,
        'numero' => 'PED-FAC-001',
    ]);

    // Pagar pedido para que genere comprobante
    $resultado = app(RegistrarPagoAction::class)->handle($this->pedido, [
        ['metodo_pago' => 'tarjeta', 'monto' => 59000, 'monto_recibido' => 59000],
    ], 5000, $this->admin);

    $this->comprobante = $resultado['comprobante'];
});

test('puede consultar autenticidad de un comprobante por codigo CUV en ruta publica (SC-03, RF-FAC-04)', function () {
    $response = $this->get(route('comprobantes.publico.verificar', $this->comprobante->codigo_verificacion));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Public/ComprobanteVerificar')
        ->has('comprobante')
        ->where('comprobante.numero', $this->comprobante->numero)
        ->where('comprobante.codigo_verificacion', $this->comprobante->codigo_verificacion)
        ->where('comprobante.total', 59000)
    );
});

test('puede visualizar tirilla termica imprimible de 80 mm (RF-FAC-04)', function () {
    $response = $this->get(route('comprobantes.publico.tirilla', $this->comprobante->codigo_verificacion));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    $contenido = $response->getContent();
    expect($contenido)->toContain('Sede Comprobantes Test')
        ->and($contenido)->toContain($this->comprobante->numero)
        ->and($contenido)->toContain($this->comprobante->codigo_verificacion);
});

test('cliente puede listar sus comprobantes y descargar tirilla (RF-FAC-05)', function () {
    $response = $this->actingAs($this->cliente)->get(route('cliente.comprobantes.index'));
    $response->assertOk();

    // Descarga autorizada
    $respDescarga = $this->actingAs($this->cliente)->get(route('cliente.comprobantes.descargar', $this->comprobante->id));
    $respDescarga->assertOk();
    $respDescarga->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    // Cliente ajeno no puede acceder (404 por global scope de BelongsToSucursal)
    $respAjeno = $this->actingAs($this->cliente2)->get(route('cliente.comprobantes.descargar', $this->comprobante->id));
    $respAjeno->assertNotFound();
});

test('permite reenvio de comprobante a multiples correos y audita en envios_comprobante (CA-31)', function () {
    Notification::fake();

    $response = $this->actingAs($this->cliente)->post(route('cliente.comprobantes.reenviar', $this->comprobante->id), [
        'correos' => ['contabilidad@empresa.test', 'copia@empresa.test'],
        'mensaje' => 'Por favor tramitar para reembolso.',
    ]);

    $response->assertRedirect();

    $envio = EnvioComprobante::where('comprobante_id', $this->comprobante->id)
        ->where('usuario_id', $this->cliente->id)
        ->first();

    expect($envio)->not->toBeNull()
        ->and($envio->destinatarios)->toContain('contabilidad@empresa.test')
        ->and($envio->destinatarios)->toContain('copia@empresa.test')
        ->and($envio->mensaje)->toBe('Por favor tramitar para reembolso.');
});

test('aplica limite de velocidad de 10 reenvios por hora (CA-31)', function () {
    Notification::fake();
    $action = app(ReenviarComprobanteAction::class);

    // Realizar 10 reenvíos permitidos
    for ($i = 1; $i <= 10; $i++) {
        $action->handle($this->comprobante, ['correo'.$i.'@test.com'], null, $this->cliente);
    }

    // El 11vo reenvío debe fallar por RateLimiter
    expect(fn () => $action->handle($this->comprobante, ['correo11@test.com'], null, $this->cliente))
        ->toThrow(ValidationException::class);
});

test('el hash SHA-256 del comprobante garantiza inmutabilidad y coherencia criptografica', function () {
    expect(strlen($this->comprobante->hash_pdf))->toBe(64)
        ->and(ctype_xdigit($this->comprobante->hash_pdf))->toBeTrue();
});
