<?php

use App\Enums\TipoBeneficioPromocion;
use App\Events\PromocionPublicada;
use App\Events\PromocionRetirada;
use App\Jobs\PublicarPromocionesJob;
use App\Models\Promocion;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Promos',
        'slug' => 'sede-promos',
        'direccion' => 'Calle 10',
        'telefono' => '+573001112233',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::create([
        'nombre' => 'Admin Promo',
        'email' => 'admin.promo@sigr.test',
        'telefono' => '+573004445566',
        'direccion' => 'Calle 10',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);
});

test('CA-20 PublicarPromocionesJob publica y retira promociones segun fecha/hora emitiendo eventos', function () {
    Event::fake([PromocionPublicada::class, PromocionRetirada::class]);

    // 1. Promoción que entra en vigencia hoy
    $promoVigente = Promocion::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Promo Activa Hoy',
        'slug' => 'promo-activa-hoy',
        'tipo_beneficio' => TipoBeneficioPromocion::PORCENTAJE,
        'valor_beneficio' => 20.00,
        'fecha_inicio' => now()->subHour(),
        'fecha_fin' => now()->addHour(),
        'activa' => true,
        'publicada' => false,
    ]);

    // 2. Promoción ya vencida pero marcada previamente como publicada
    $promoVencida = Promocion::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Promo Expirada',
        'slug' => 'promo-expirada',
        'tipo_beneficio' => TipoBeneficioPromocion::VALOR_FIJO,
        'valor_beneficio' => 5000.00,
        'fecha_inicio' => now()->subDays(5),
        'fecha_fin' => now()->subDay(),
        'activa' => true,
        'publicada' => true,
    ]);

    // 3. Ejecutar el Job programado
    $job = new PublicarPromocionesJob;
    $resultado = $job->handle();

    expect($resultado['publicadas'])->toBe(1);
    expect($resultado['retiradas'])->toBe(1);

    expect($promoVigente->fresh()->publicada)->toBeTrue();
    expect($promoVencida->fresh()->publicada)->toBeFalse();

    Event::assertDispatched(PromocionPublicada::class);
    Event::assertDispatched(PromocionRetirada::class);
});

test('calculo correcto de precios promocionales segun el tipo de beneficio', function () {
    $promoPorcentaje = new Promocion([
        'tipo_beneficio' => TipoBeneficioPromocion::PORCENTAJE,
        'valor_beneficio' => 15.00,
    ]);
    // 15% de $30.000 = $4.500 de ahorro -> precio final $25.500
    expect($promoPorcentaje->calcularPrecioPromocional(30000.00))->toBe(25500.00);

    $promoFijo = new Promocion([
        'tipo_beneficio' => TipoBeneficioPromocion::VALOR_FIJO,
        'valor_beneficio' => 5000.00,
    ]);
    // $30.000 - $5.000 = $25.000
    expect($promoFijo->calcularPrecioPromocional(30000.00))->toBe(25000.00);

    $promoEspecial = new Promocion([
        'tipo_beneficio' => TipoBeneficioPromocion::PRECIO_ESPECIAL,
        'valor_beneficio' => 19900.00,
    ]);
    // Precio especial fijado
    expect($promoEspecial->calcularPrecioPromocional(30000.00))->toBe(19900.00);
});

test('administrador de sucursal puede registrar y gestionar promociones (RF-PRO-01..05)', function () {
    $this->actingAs($this->admin);
    session(['sucursal_id' => $this->sucursal->id]);

    $response = $this->post(route('admin.promociones.store'), [
        'nombre' => 'Super Descuento Almuerzos',
        'tipo_beneficio' => TipoBeneficioPromocion::PORCENTAJE->value,
        'valor_beneficio' => 10.00,
        'fecha_inicio' => now()->format('Y-m-d'),
        'fecha_fin' => now()->addDays(10)->format('Y-m-d'),
        'aplica_a_todo' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $promo = Promocion::where('slug', 'super-descuento-almuerzos')->first();
    expect($promo)->not->toBeNull()
        ->and($promo->sucursal_id)->toBe($this->sucursal->id)
        ->and((float) $promo->valor_beneficio)->toBe(10.00)
        ->and($promo->publicada)->toBeTrue();
});
