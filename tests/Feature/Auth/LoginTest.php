<?php

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Centro',
        'slug' => 'sede-centro',
        'direccion' => 'Calle 10 # 4-20',
        'telefono' => '+573001234567',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);
});

test('superadministrador es redirigido a /superadmin al iniciar sesion (CA-04)', function () {
    $superadmin = User::create([
        'nombre' => 'Super Admin',
        'email' => 'superadmin@sigr.test',
        'telefono' => '+573001111111',
        'direccion' => 'Central',
        'password' => Hash::make('Password123!'),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $superadmin->assignRole('super_admin');

    $response = $this->post('/login', [
        'email' => 'superadmin@sigr.test',
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/superadmin');
    $this->assertAuthenticatedAs($superadmin);
});

test('administrador de sucursal es redirigido a /admin y fija sucursal en sesion (CA-04)', function () {
    $admin = User::create([
        'nombre' => 'Admin Sede',
        'email' => 'admin@sigr.test',
        'telefono' => '+573002222222',
        'direccion' => 'Sede Centro',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $admin->assignRole('admin');
    $admin->sucursales()->attach($this->sucursal->id);

    $response = $this->post('/login', [
        'email' => 'admin@sigr.test',
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin);
    expect(session('sucursal_id'))->toBe($this->sucursal->id);
});

test('mesero es redirigido a /mesero al iniciar sesion (CA-04)', function () {
    $mesero = User::create([
        'nombre' => 'Mesero 1',
        'email' => 'mesero@sigr.test',
        'telefono' => '+573003333333',
        'direccion' => 'Sede Centro',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $mesero->assignRole('mesero');
    $mesero->sucursales()->attach($this->sucursal->id);

    $response = $this->post('/login', [
        'email' => 'mesero@sigr.test',
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/mesero');
    $this->assertAuthenticatedAs($mesero);
});

test('cliente verificado es redirigido a /cliente al iniciar sesion (CA-04)', function () {
    $cliente = User::create([
        'nombre' => 'Cliente Feliz',
        'email' => 'cliente@sigr.test',
        'telefono' => '+573004444444',
        'direccion' => 'Carrera 7 # 12-34',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $cliente->assignRole('cliente');

    $response = $this->post('/login', [
        'email' => 'cliente@sigr.test',
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/cliente');
    $this->assertAuthenticatedAs($cliente);
});

test('bloqueo por limitador de tasa tras 5 intentos fallidos en 1 minuto (RF-USR-06, RNF-SEG-03)', function () {
    RateLimiter::clear('login');

    // Realizar 5 intentos fallidos
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/login', [
            'email' => 'intruso@sigr.test',
            'password' => 'clave-erronea',
        ]);
        $response->assertSessionHasErrors();
    }

    // El 6to intento debe ser bloqueado por el throttle con status 429
    $response = $this->post('/login', [
        'email' => 'intruso@sigr.test',
        'password' => 'clave-erronea',
    ]);

    $response->assertStatus(429);
});
