<?php

use App\Actions\Reservas\CrearReservaAction;
use App\Enums\EstadoEspacio;
use App\Enums\EstadoReserva;
use App\Enums\OrigenUsuario;
use App\Events\EspacioActualizado;
use App\Events\ReservaActualizada;
use App\Events\ReservaCreada;
use App\Models\CategoriaReserva;
use App\Models\Espacio;
use App\Models\HorarioAtencion;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Models\TipoMesa;
use App\Models\User;
use App\Notifications\InvitacionClienteNotification;
use App\Notifications\ReservaConfirmadaNotification;
use App\Policies\ReservaPolicy;
use App\Services\ConfiguracionService;
use Carbon\Carbon;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Reserva Test',
        'slug' => 'sede-reserva-test',
        'direccion' => 'Calle 80 # 11-40',
        'telefono' => '+573005556677',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    for ($dia = 0; $dia <= 6; $dia++) {
        HorarioAtencion::create([
            'sucursal_id' => $this->sucursal->id,
            'dia_semana' => $dia,
            'hora_apertura' => '12:00:00',
            'hora_cierre' => '22:00:00',
            'activo' => true,
        ]);
    }

    $this->tipoMesa = TipoMesa::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 4',
        'capacidad_minima' => 2,
        'capacidad_maxima' => 4,
        'es_abierta' => false,
        'activo' => true,
    ]);

    $this->espacio = Espacio::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Mesa 1',
        'tipo' => 'mesa',
        'tipo_mesa_id' => $this->tipoMesa->id,
        'capacidad' => 4,
        'estado' => EstadoEspacio::LIBRE,
        'activo' => true,
    ]);

    $this->categoria = CategoriaReserva::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre' => 'Cena Estándar',
        'descripcion' => 'Cena normal',
        'activo' => true,
    ]);

    $this->admin = User::create([
        'nombre' => 'Admin Sede',
        'email' => 'admin.reserva@sigr.test',
        'password' => Hash::make('password123'),
        'telefono' => '+573001110000',
        'direccion' => 'Calle 100 # 10-20',
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);

    $this->cliente = User::create([
        'nombre' => 'Cliente Registrado',
        'email' => 'cliente.reserva@sigr.test',
        'password' => Hash::make('password123'),
        'telefono' => '+573002220000',
        'direccion' => 'Carrera 7 # 45-67',
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->cliente->assignRole('cliente');

    ConfiguracionService::set('reserva.duracion_minutos', 120, $this->sucursal);
    ConfiguracionService::set('reserva.intervalo_minutos', 30, $this->sucursal);
    ConfiguracionService::set('reserva.aprobacion_automatica', false, $this->sucursal);
});

test('crea reserva pública para invitado creando cuenta implícita con enlace de 72h (CA-01, CA-12)', function () {
    Event::fake([ReservaCreada::class]);
    Notification::fake();

    $fechaManana = Carbon::tomorrow()->toDateString();

    $response = $this->post(route('reservas.publico.store'), [
        'sucursal_id' => $this->sucursal->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00',
        'personas' => 4,
        'categoria_reserva_id' => $this->categoria->id,
        'nombre_contacto' => 'Laura Invitada',
        'email_contacto' => 'laura.invitada@test.com',
        'telefono_contacto' => '3009991122',
        'notas' => 'Cerca de ventana',
    ]);

    $response->assertRedirect();

    // Validar creación de reserva
    $this->assertDatabaseHas('reservas', [
        'sucursal_id' => $this->sucursal->id,
        'email_contacto' => 'laura.invitada@test.com',
        'personas' => 4,
        'estado' => EstadoReserva::PENDIENTE->value,
    ]);

    // Validar creación implícita de usuario cliente con origen reserva
    $this->assertDatabaseHas('users', [
        'email' => 'laura.invitada@test.com',
        'origen' => OrigenUsuario::RESERVA->value,
    ]);

    // Validar notificación con enlace de 72 horas
    Notification::assertSentTo(
        User::where('email', 'laura.invitada@test.com')->first(),
        InvitacionClienteNotification::class
    );

    // Validar evento WebSocket emitido
    Event::assertDispatched(ReservaCreada::class);
});

test('aprueba automáticamente si el parámetro reserva.aprobacion_automatica está activo', function () {
    Event::fake();
    Notification::fake();

    ConfiguracionService::set('reserva.aprobacion_automatica', true, $this->sucursal);

    $action = app(CrearReservaAction::class);
    $fechaManana = Carbon::tomorrow()->toDateString();

    $reserva = $action->handle([
        'sucursal_id' => $this->sucursal->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '14:00',
        'personas' => 4,
        'nombre_contacto' => 'Pedro Auto',
        'email_contacto' => 'pedro.auto@test.com',
        'telefono_contacto' => '3110009988',
    ]);

    expect($reserva->estado)->toBe(EstadoReserva::CONFIRMADA);
    $usuarioCreado = User::where('email', 'pedro.auto@test.com')->first();
    expect($usuarioCreado)->not->toBeNull();
    Notification::assertSentTo($usuarioCreado, ReservaConfirmadaNotification::class);
});

test('administrador aprueba reserva pendiente cambiándola a CONFIRMADA (RF-RES-07)', function () {
    Event::fake([ReservaActualizada::class]);
    Notification::fake();

    $fechaManana = Carbon::tomorrow()->toDateString();
    $reserva = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaManana),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::PENDIENTE,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.reservas.aprobar', $reserva));

    $response->assertRedirect();
    expect($reserva->fresh()->estado)->toBe(EstadoReserva::CONFIRMADA);
    Event::assertDispatched(ReservaActualizada::class);
});

test('administrador reprograma fecha u hora de reserva activa (RF-RES-09)', function () {
    Event::fake([ReservaActualizada::class]);

    $fechaManana = Carbon::tomorrow()->toDateString();
    $reserva = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaManana),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    $fechaFutura = Carbon::now()->addDays(3)->toDateString();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.reservas.reprogramar', $reserva), [
            'nueva_fecha' => $fechaFutura,
            'nueva_hora_inicio' => '16:00',
        ]);

    $response->assertRedirect();

    $reservaActualizada = $reserva->fresh();
    expect($reservaActualizada->fecha->toDateString())->toBe($fechaFutura);
    expect(substr($reservaActualizada->hora_inicio, 0, 5))->toBe('16:00');
    expect(substr($reservaActualizada->hora_fin, 0, 5))->toBe('18:00');
    Event::assertDispatched(ReservaActualizada::class);
});

test('cliente puede auto-cancelar su reserva activa con motivo (CA-23)', function () {
    Event::fake([ReservaActualizada::class]);

    $fechaManana = Carbon::tomorrow()->toDateString();
    $reserva = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaManana),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    $response = $this->actingAs($this->cliente)
        ->post(route('cliente.reservas.cancelar', $reserva), [
            'motivo' => 'Surgió un imprevisto y no podremos asistir.',
        ]);

    $response->assertRedirect();

    $reservaActualizada = $reserva->fresh();
    expect($reservaActualizada->estado)->toBe(EstadoReserva::CANCELADA);
    expect($reservaActualizada->motivo_cancelacion)->toBe('Surgió un imprevisto y no podremos asistir.');
    expect($reservaActualizada->cancelado_por_id)->toBe($this->cliente->id);
    Event::assertDispatched(ReservaActualizada::class);
});

test('cliente no puede cancelar reservas de otros clientes', function () {
    $otroCliente = User::create([
        'nombre' => 'Otro Cliente',
        'email' => 'otro@sigr.test',
        'password' => Hash::make('password123'),
        'telefono' => '+573003334455',
        'direccion' => 'Avenida Siempre Viva 123',
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $otroCliente->assignRole('cliente');

    $fechaManana = Carbon::tomorrow()->toDateString();
    $reserva = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaManana),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    // Verificar política de autorización
    $policy = new ReservaPolicy;
    expect($policy->cancel($otroCliente, $reserva))->toBeFalse();

    // Vía HTTP, el scope global o política rechazan la operación (403 o 404)
    $response = $this->actingAs($otroCliente)
        ->post(route('cliente.reservas.cancelar', $reserva), [
            'motivo' => 'Intento malicioso',
        ]);

    expect(in_array($response->status(), [403, 404]))->toBeTrue();
    expect($reserva->fresh()->estado)->toBe(EstadoReserva::CONFIRMADA);
});

test('administrador marca asistencia cumplida o no asistió (RF-RES-10)', function () {
    Event::fake([ReservaActualizada::class]);

    $fechaHoy = Carbon::today()->toDateString();
    $reserva1 = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaHoy),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaHoy,
        'hora_inicio' => '12:00:00',
        'hora_fin' => '14:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    $reserva2 = Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => 'RES-TEST-NO-ASISTIO',
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaHoy,
        'hora_inicio' => '15:00:00',
        'hora_fin' => '17:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    // Marcar como cumplida
    $response = $this->actingAs($this->admin)
        ->post(route('admin.reservas.asistencia', $reserva1), [
            'asistio' => true,
        ]);

    $response->assertRedirect();
    expect($reserva1->fresh()->estado)->toBe(EstadoReserva::CUMPLIDA);

    // Marcar como no asistió en la segunda reserva
    $response2 = $this->actingAs($this->admin)
        ->post(route('admin.reservas.asistencia', $reserva2), [
            'asistio' => false,
        ]);

    $response2->assertRedirect();
    expect($reserva2->fresh()->estado)->toBe(EstadoReserva::NO_ASISTIO);
});

test('emite evento EspacioActualizado en canal WebSocket sucursal.id.mesas al cambiar estado (CA-24)', function () {
    Event::fake([EspacioActualizado::class]);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.espacios.update', $this->espacio), [
            'nombre' => 'Mesa 1 VIP',
            'tipo' => 'mesa',
            'tipo_mesa_id' => $this->tipoMesa->id,
            'capacidad' => 4,
            'estado' => 'ocupado',
        ]);

    $response->assertRedirect();
    expect($this->espacio->fresh()->estado)->toBe(EstadoEspacio::OCUPADO);
    Event::assertDispatched(EspacioActualizado::class, function ($event) {
        return $event->espacio->id === $this->espacio->id
            && $event->broadcastOn()[0]->name === "sucursal.{$this->sucursal->id}.mesas";
    });
});

test('rechaza eliminación de mesa si tiene reservas vinculadas activas (RF-AUD-05)', function () {
    $fechaManana = Carbon::tomorrow()->toDateString();
    Reserva::create([
        'sucursal_id' => $this->sucursal->id,
        'codigo' => Reserva::generarCodigo($this->sucursal->id, $fechaManana),
        'cliente_id' => $this->cliente->id,
        'espacio_id' => $this->espacio->id,
        'fecha' => $fechaManana,
        'hora_inicio' => '13:00:00',
        'hora_fin' => '15:00:00',
        'personas' => 4,
        'estado' => EstadoReserva::CONFIRMADA,
        'nombre_contacto' => $this->cliente->nombre,
        'email_contacto' => $this->cliente->email,
        'telefono_contacto' => $this->cliente->telefono,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('admin.espacios.destroy', $this->espacio));

    $response->assertSessionHasErrors();
    expect($this->espacio->fresh()->trashed())->toBeFalse();
});
