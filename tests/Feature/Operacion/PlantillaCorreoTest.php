<?php

use App\Models\PlantillaCorreo;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\PlantillaRenderer;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Plantillas Test',
        'slug' => 'sede-plantillas-test',
        'direccion' => 'Calle 100 # 20-30',
        'telefono' => '+573009990000',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::factory()->create([
        'email' => 'admin.plantillas@sigr.test',
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->plantilla = PlantillaCorreo::create([
        'sucursal_id' => $this->sucursal->id,
        'clave' => 'comprobante',
        'nombre' => 'Comprobante de Venta Electrónico (FAC)',
        'asunto' => 'Comprobante de Pago {{comprobante.numero}} - {{restaurante.nombre}}',
        'cuerpo_html' => '<p>Estimado/a <strong>{{cliente.nombre}}</strong>,</p><p>Adjuntamos su comprobante {{comprobante.numero}} por valor de {{comprobante.total}}.</p>',
        'variables_disponibles' => ['cliente.nombre', 'comprobante.numero', 'comprobante.total', 'restaurante.nombre'],
        'activa' => true,
    ]);
});

test('renderiza variables correctamente con strtr sin compilar Blade por seguridad (RF-CFG-05, CA-34)', function () {
    $html = '<p>Hola {{cliente.nombre}}, tu pedido {{pedido.numero}} está listo. Blade test: {{ 7 * 7 }}</p>';
    $variables = [
        'cliente.nombre' => 'Mateo Silva',
        'pedido.numero' => 'PED-99',
    ];

    $render = PlantillaRenderer::render($html, $variables);

    // Las variables conocidas fueron sustituidas
    expect($render)->toContain('Hola Mateo Silva')
        ->and($render)->toContain('PED-99');

    // La sintaxis de Blade NO fue evaluada como código PHP ejecutable
    expect($render)->not->toContain('49')
        ->and($render)->toContain('{{ 7 * 7 }}');
});

test('HTMLPurifier sanea scripts, iframes y atributos maliciosos (CA-35)', function () {
    $malicioso = '<p>Texto seguro</p><script>alert("xss")</script><iframe src="https://evil.site"></iframe><a href="javascript:steal()">Click</a>';

    $saneado = PlantillaRenderer::sanitizar($malicioso);

    expect($saneado)->toContain('Texto seguro')
        ->and($saneado)->not->toContain('<script>')
        ->and($saneado)->not->toContain('<iframe>')
        ->and($saneado)->not->toContain('javascript:');
});

test('admin puede listar plantillas y actualizar plantilla con sanitizacion (RF-CFG-04, RF-CFG-06)', function () {
    $respIndex = $this->actingAs($this->admin)->get(route('admin.plantillas-correo.index', [
        'sucursal_id' => $this->sucursal->id,
    ]));
    $respIndex->assertOk();

    // Actualizar con intento de inyección de script
    $respUpdate = $this->actingAs($this->admin)->put(route('admin.plantillas-correo.update', $this->plantilla->id), [
        'asunto' => 'Nuevo Asunto {{comprobante.numero}}',
        'cuerpo_html' => '<div><p>Estimado {{cliente.nombre}}</p><script>evil()</script></div>',
    ]);

    $respUpdate->assertRedirect();

    $this->plantilla->refresh();
    expect($this->plantilla->asunto)->toBe('Nuevo Asunto {{comprobante.numero}}')
        ->and($this->plantilla->cuerpo_html)->not->toContain('<script>');
});

test('admin puede obtener vista previa JSON renderizada (RF-CFG-07)', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.plantillas-correo.vista-previa', $this->plantilla->id));

    $response->assertOk();
    $data = $response->json();

    expect($data)->toHaveKeys(['asunto', 'html'])
        ->and($data['asunto'])->toContain('Comprobante de Pago')
        ->and($data['html'])->toContain('Diana Marcela López'); // Nombre de prueba de la clave comprobante
});

test('admin puede enviar correo electronico de prueba (RF-CFG-08)', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.plantillas-correo.enviar-prueba', $this->plantilla->id));

    $response->assertRedirect();

    Mail::assertSentCount(1);
});
