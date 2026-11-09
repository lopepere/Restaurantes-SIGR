<?php

use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Enums\EstadoReserva;
use App\Enums\TipoPedido;
use App\Models\Espacio;
use App\Models\HorarioAtencion;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Models\TipoMesa;
use App\Services\ConfiguracionService;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Disponibilidad Test',
        'slug' => 'sede-disponibilidad-test',
        'direccion' => 'Calle 100 # 15-20',
        'telefono' => '+573001112233',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    // Horario: todos los días de 12:00 a 22:00
    for ($dia = 0; $dia <= 6; $dia++) {
        HorarioAtencion::create([
            'sucursal_id' => $this->sucursal->id,
            'dia_semana' => $dia,
            'hora_apertura' => '12:00:00',
            'hora_cierre' => '22:00:00',
            'activo' => true,
        ]);
    }

    // Tipos de mesa
    $this->tipoMesa2 = TipoMesa::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 2',
        'capacidad_minima' => 1,
        'capacidad_maxima' => 2,
        'es_abierta' => false,
        'activo' => true,
    ]);

    $this->tipoMesa4 = TipoMesa::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 4',
        'capacidad_minima' => 2,
        'capacidad_maxima' => 4,
        'es_abierta' => false,
        'activo' => true,
    ]);

    $this->tipoAbierta = TipoMesa::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Salón Eventos',
        'capacidad_minima' => 10,
        'capacidad_maxima' => 50,
        'es_abierta' => true,
        'activo' => true,
    ]);

    // Espacios físicos
    $this->mesa2 = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 101',
        'tipo' => 'mesa',
        'tipo_mesa_id' => $this->tipoMesa2->id,
        'capacidad' => 2,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    $this->mesa4 = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 201',
        'tipo' => 'mesa',
        'tipo_mesa_id' => $this->tipoMesa4->id,
        'capacidad' => 4,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    $this->salon = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Salón Principal',
        'tipo' => 'salon',
        'tipo_mesa_id' => $this->tipoAbierta->id,
        'capacidad' => 30,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    // Parámetros de configuración
    ConfiguracionService::set('reserva.duracion_minutos', 120, $this->sucursal);
    ConfiguracionService::set('reserva.intervalo_minutos', 60, $this->sucursal);
    ConfiguracionService::set('reserva.anticipacion_minima_horas', 2, $this->sucursal);
});

test('genera franjas horarias dentro del horario de atención respetando la duración', function () {
    // Para una fecha futura
    $fecha = Carbon::tomorrow()->toDateString();

    $franjas = DisponibilidadService::franjas($this->sucursal->id, $fecha, 2);

    expect($franjas)->not->toBeEmpty();
    // Primera franja debe ser 12:00
    expect($franjas[0]['hora_inicio'])->toBe('12:00');
    expect($franjas[0]['hora_fin'])->toBe('14:00');
    expect($franjas[0]['disponible'])->toBeTrue();

    // Última franja con duración 120m y cierre 22:00 debe terminar a más tardar a las 22:00 (inicio 20:00)
    $ultimaFranja = end($franjas);
    expect($ultimaFranja['hora_inicio'])->toBe('20:00');
    expect($ultimaFranja['hora_fin'])->toBe('22:00');
    expect($ultimaFranja['disponible'])->toBeTrue();
});

test('excluye mesas ocupadas por reservas activas solapadas en el mismo horario (CA-22)', function () {
    $fecha = Carbon::tomorrow()->toDateString();

    // Crear una reserva confirmada en la mesa 201 de 13:00 a 15:00
    Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => 'RES-TEST-001',
        'espacio_id' => $this->mesa4->id,
        'fecha' => $fecha,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => 'Carlos Cliente',
        'email_contacto' => 'carlos@test.com',
        'telefono_contacto' => '3001234567',
    ]);

    // Buscamos disponibilidad para 4 personas (solo la mesa4 tiene esa capacidad exacta no-abierta)
    $franjas = DisponibilidadService::franjas($this->sucursal->id, $fecha, 4);

    // Franja 13:00 - 15:00 debe estar NO disponible
    $franja13 = collect($franjas)->firstWhere('hora_inicio', '13:00');
    expect($franja13['disponible'])->toBeFalse();
    expect($franja13['motivo'])->toBe('Sin mesas disponibles');

    // Franja 15:00 - 17:00 debe estar disponible (la reserva anterior terminó a las 15:00)
    $franja15 = collect($franjas)->firstWhere('hora_inicio', '15:00');
    expect($franja15['disponible'])->toBeTrue();
});

test('admite grupos grandes en tipo de mesa abierta respetando mínimo comensales', function () {
    $fecha = Carbon::tomorrow()->toDateString();

    // Intento con 8 personas no cumple el mínimo de 10 de la opción abierta ni cabe en mesas de 4
    $franjas8 = DisponibilidadService::franjas($this->sucursal->id, $fecha, 8);
    $todasDisponibles8 = collect($franjas8)->every(fn ($f) => $f['disponible'] === false);
    expect($todasDisponibles8)->toBeTrue();

    // Intento con 12 personas sí cumple el mínimo de 10 de la opción abierta
    $franjas12 = DisponibilidadService::franjas($this->sucursal->id, $fecha, 12);
    $algunaDisponible12 = collect($franjas12)->some(fn ($f) => $f['disponible'] === true);
    expect($algunaDisponible12)->toBeTrue();
});

test('excluye mesas ocupadas hoy por pedidos activos en salón', function () {
    $fechaHoy = Carbon::today()->toDateString();

    // Crear pedido activo en salón en mesa 101
    Pedido::create([
        'sucursal_id' => $this->sucursal->id,
        'espacio_id' => $this->mesa2->id,
        'numero' => 'PED-TEST-MESA',
        'tipo' => TipoPedido::EN_SITIO,
        'estado' => EstadoPedido::EN_PREPARACION,
        'subtotal' => 20000,
        'impuestos' => 1600,
        'propina' => 0,
        'total' => 21600,
        'pagado' => false,
    ]);

    // Al consultar disponibilidad para 2 personas hoy, mesa 101 está excluida
    $franjas = DisponibilidadService::franjas($this->sucursal->id, $fechaHoy, 2);

    // Como mesa 101 está ocupada por pedido activo, sólo mesa4 podría usarse si permite 2 personas (min 2, max 4)
    $franjaDisponible = collect($franjas)->firstWhere('disponible', true);
    if ($franjaDisponible) {
        $espacioAsignado = DisponibilidadService::asignarMejorEspacio($this->sucursal->id, $fechaHoy, $franjaDisponible['hora_inicio'], $franjaDisponible['hora_fin'], 2);
        // Debe asignar mesa 201 (mesa4), no mesa 101
        expect($espacioAsignado->id)->toBe($this->mesa4->id);
    }
});

test('aplica anticipación mínima de reserva para el mismo día', function () {
    $fechaHoy = Carbon::today()->toDateString();

    // Fijamos la hora simulada a las 14:00
    Carbon::setTestNow(Carbon::today()->setTime(14, 0, 0));

    // Con anticipación mínima de 2 horas, cualquier franja antes de las 16:00 debe rechazarse por anticipación
    $franjas = DisponibilidadService::franjas($this->sucursal->id, $fechaHoy, 2);

    $franja12 = collect($franjas)->firstWhere('hora_inicio', '12:00');
    $franja13 = collect($franjas)->firstWhere('hora_inicio', '13:00');
    $franja14 = collect($franjas)->firstWhere('hora_inicio', '14:00');
    $franja15 = collect($franjas)->firstWhere('hora_inicio', '15:00');

    expect($franja12['disponible'])->toBeFalse();
    expect($franja12['motivo'])->toBe('Anticipación mínima requerida');

    expect($franja15['disponible'])->toBeFalse();
    expect($franja15['motivo'])->toBe('Anticipación mínima requerida');

    // A partir de las 16:00 sí debe estar disponible si hay mesa
    $franja16 = collect($franjas)->firstWhere('hora_inicio', '16:00');
    expect($franja16['disponible'])->toBeTrue();

    Carbon::setTestNow();
});
