<?php

use App\Enums\OrigenUsuario;
use App\Models\User;
use App\Notifications\InvitacionClienteNotification;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

test('visitante puede registrarse voluntariamente, recibe correo de verificacion y se restringe acceso hasta verificar (CA-32, SC-01, RF-USR-14, 15)', function () {
    Notification::fake();

    $response = $this->post('/registro', [
        'nombre' => 'Carlos Perez',
        'email' => 'carlos@ejemplo.com',
        'telefono' => '+573109876543',
        'direccion' => 'Calle 100 # 15-20',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'politica_aceptada' => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'carlos@ejemplo.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->nombre)->toBe('Carlos Perez')
        ->and($user->hasRole('cliente'))->toBeTrue()
        ->and($user->origen)->toBe(OrigenUsuario::REGISTRO)
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);

    // Intentar acceder al dashboard de cliente sin verificar correo debe ser bloqueado por middleware verified
    $this->actingAs($user);
    $dashboardResponse = $this->get('/cliente');
    $dashboardResponse->assertRedirect(route('verification.notice'));

    // Marcar como verificado y comprobar acceso permitido
    $user->markEmailAsVerified();
    $verifiedDashboardResponse = $this->get('/cliente');
    $verifiedDashboardResponse->assertOk();
});

test('registro falla si no se acepta la politica de tratamiento de datos personales (RN-USR-01, Ley 1581)', function () {
    $response = $this->post('/registro', [
        'nombre' => 'Carlos Perez',
        'email' => 'carlos2@ejemplo.com',
        'telefono' => '+573109876544',
        'direccion' => 'Calle 100 # 15-20',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'politica_aceptada' => '0',
    ]);

    $response->assertSessionHasErrors(['politica_aceptada']);
});

test('registro falla con mensaje de duplicado si el correo ya existe (CA-02)', function () {
    User::create([
        'nombre' => 'Existente',
        'email' => 'existente@ejemplo.com',
        'telefono' => '+573101111111',
        'direccion' => 'Calle 1',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
    ]);

    $response = $this->post('/registro', [
        'nombre' => 'Nuevo Intentando Correo',
        'email' => 'existente@ejemplo.com',
        'telefono' => '+573102222222',
        'direccion' => 'Calle 2',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'politica_aceptada' => '1',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('registro falla con mensaje descriptivo si el telefono ya existe incluso con formato diferente (CA-03, RN-USR-02)', function () {
    User::create([
        'nombre' => 'Existente Telefono',
        'email' => 'usuario1@ejemplo.com',
        'telefono' => '+573001234567',
        'direccion' => 'Calle 1',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
    ]);

    // Intenta registrarse con el mismo número pero con espacios o formato local
    $response = $this->post('/registro', [
        'nombre' => 'Nuevo Intentando Telefono',
        'email' => 'usuario2@ejemplo.com',
        'telefono' => '300 123 4567',
        'direccion' => 'Calle 2',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'politica_aceptada' => '1',
    ]);

    $response->assertSessionHasErrors(['telefono' => 'El teléfono ya está registrado.']);
});

test('registro con correo de cuenta no activada creada por pedido previo reenvia invitacion sin duplicar (CA-37, RN-USR-12)', function () {
    Notification::fake();

    $cuentaPedido = User::create([
        'nombre' => 'Cliente Por Pedido',
        'email' => 'pedido@ejemplo.com',
        'telefono' => '+573009998877',
        'direccion' => 'Calle 50 # 10-20',
        'password' => null, // No ha definido contraseña todavía
        'origen' => OrigenUsuario::PEDIDO,
        'invitacion_token' => 'token-previo-123',
        'invitacion_enviada_at' => now()->subHours(2),
        'invitacion_aceptada_at' => null,
        'activo' => true,
    ]);

    $conteoInicial = User::count();

    $response = $this->post('/registro', [
        'nombre' => 'Cliente Por Pedido',
        'email' => 'pedido@ejemplo.com',
        'telefono' => '+573009998877',
        'direccion' => 'Calle 50 # 10-20',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'politica_aceptada' => '1',
    ]);

    // Debe retornar error de validación en email explicando que se reenvió la invitación
    $response->assertSessionHasErrors(['email']);
    expect(User::count())->toBe($conteoInicial);

    Notification::assertSentTo($cuentaPedido, InvitacionClienteNotification::class);
});
