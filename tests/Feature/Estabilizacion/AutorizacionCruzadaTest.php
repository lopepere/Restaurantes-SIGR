<?php

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursalNorte = Sucursal::create([
        'nombre' => 'Sede Norte Auto Test',
        'slug' => 'sede-norte-auto-test',
        'direccion' => 'Calle 100 # 10-20',
        'telefono' => '+573001112244',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->sucursalSur = Sucursal::create([
        'nombre' => 'Sede Sur Auto Test',
        'slug' => 'sede-sur-auto-test',
        'direccion' => 'Carrera 10 # 20-30',
        'telefono' => '+573002223355',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->adminNorte = User::factory()->create();
    $this->adminNorte->assignRole('admin');
    $this->adminNorte->sucursales()->attach($this->sucursalNorte->id);

    $this->meseroNorte = User::factory()->create();
    $this->meseroNorte->assignRole('mesero');
    $this->meseroNorte->sucursales()->attach($this->sucursalNorte->id);

    $this->cliente = User::factory()->create();
    $this->cliente->assignRole('cliente');
});

test('visitante no autenticado es redirigido a login al intentar acceder a paneles protegidos', function () {
    $this->get(route('admin.dashboard'))->assertRedirect('/login');
    $this->get(route('mesero.dashboard'))->assertRedirect('/login');
    $this->get(route('cliente.dashboard'))->assertRedirect('/login');
    $this->get('/superadmin')->assertRedirect('/login');
});

test('cliente no puede acceder a rutas de mesero, admin ni superadmin', function () {
    $this->actingAs($this->cliente)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->cliente)->get(route('mesero.dashboard'))->assertForbidden();
    $this->actingAs($this->cliente)->get('/superadmin')->assertForbidden();
});

test('mesero no puede acceder a rutas de administracion ni de superadmin', function () {
    $this->actingAs($this->meseroNorte)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->meseroNorte)->get('/superadmin')->assertForbidden();
    $this->actingAs($this->meseroNorte)->get(route('cliente.dashboard'))->assertForbidden();
});

test('administrador de sucursal no puede acceder a panel superadmin', function () {
    $this->actingAs($this->adminNorte)->get('/superadmin')->assertForbidden();
});

test('administrador de sede Norte recibe 403 Forbidden al intentar acceder a modulos de sede Sur', function () {
    // Pedidos
    $this->actingAs($this->adminNorte)->get(route('admin.pedidos.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Comprobantes
    $this->actingAs($this->adminNorte)->get(route('admin.comprobantes.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Caja
    $this->actingAs($this->adminNorte)->get(route('admin.caja.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Compras
    $this->actingAs($this->adminNorte)->get(route('admin.compras.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Inventario
    $this->actingAs($this->adminNorte)->get(route('admin.inventario.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Informes
    $this->actingAs($this->adminNorte)->get(route('admin.informes.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();

    // Reservas
    $this->actingAs($this->adminNorte)->get(route('admin.reservas.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertForbidden();
});

test('superadministrador tiene acceso global y puede consultar cualquier sucursal', function () {
    $this->actingAs($this->superAdmin)->get(route('admin.pedidos.index', [
        'sucursal_id' => $this->sucursalSur->id,
    ]))->assertOk();

    $this->actingAs($this->superAdmin)->get(route('admin.comprobantes.index', [
        'sucursal_id' => $this->sucursalNorte->id,
    ]))->assertOk();
});
