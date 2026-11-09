<?php

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursalCentro = Sucursal::create([
        'nombre' => 'Sede Centro',
        'slug' => 'sede-centro',
        'direccion' => 'Calle 10 # 4-20',
        'telefono' => '+573001234567',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->sucursalNorte = Sucursal::create([
        'nombre' => 'Sede Norte',
        'slug' => 'sede-norte',
        'direccion' => 'Calle 150 # 15-30',
        'telefono' => '+573007654321',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    // Admin Sede Centro
    $this->adminCentro = User::create([
        'nombre' => 'Admin Centro',
        'email' => 'admin.centro@sigr.test',
        'telefono' => '+573001112233',
        'direccion' => 'Calle 10',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->adminCentro->assignRole('admin');
    $this->adminCentro->sucursales()->attach($this->sucursalCentro->id);

    // Admin Sede Norte
    $this->adminNorte = User::create([
        'nombre' => 'Admin Norte',
        'email' => 'admin.norte@sigr.test',
        'telefono' => '+573004445566',
        'direccion' => 'Calle 150',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->adminNorte->assignRole('admin');
    $this->adminNorte->sucursales()->attach($this->sucursalNorte->id);

    // Mesero asignado exclusivamente a Sede Centro
    $this->meseroCentro = User::create([
        'nombre' => 'Mesero Exclusivo Centro',
        'email' => 'mesero.centro@sigr.test',
        'telefono' => '+573007778899',
        'direccion' => 'Calle 12',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->meseroCentro->assignRole('mesero');
    $this->meseroCentro->sucursales()->attach($this->sucursalCentro->id);

    // Mesero asignado exclusivamente a Sede Norte
    $this->meseroNorte = User::create([
        'nombre' => 'Mesero Exclusivo Norte',
        'email' => 'mesero.norte@sigr.test',
        'telefono' => '+573009990000',
        'direccion' => 'Calle 155',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->meseroNorte->assignRole('mesero');
    $this->meseroNorte->sucursales()->attach($this->sucursalNorte->id);
});

test('administrador de sede Norte no puede ver ni acceder a meseros de sede Centro en el listado (CA-30, RN-CFG-02)', function () {
    $this->actingAs($this->adminNorte);
    session(['sucursal_id' => $this->sucursalNorte->id]);

    $response = $this->get(route('admin.meseros.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Meseros/Index')
        ->has('meseros.data', 1)
        ->where('meseros.data.0.email', 'mesero.norte@sigr.test')
    );
});

test('administrador de sede Norte recibe 403 Forbidden al intentar actualizar un mesero de sede Centro (CA-30, RN-USR-07)', function () {
    $this->actingAs($this->adminNorte);
    session(['sucursal_id' => $this->sucursalNorte->id]);

    $response = $this->put(route('admin.meseros.update', $this->meseroCentro), [
        'nombre' => 'Ataque Cruzado',
        'email' => $this->meseroCentro->email,
        'telefono' => $this->meseroCentro->telefono,
        'direccion' => 'Calle Modificada',
        'activo' => false,
    ]);

    $response->assertForbidden();

    // Comprobar que los datos del mesero no fueron alterados
    expect($this->meseroCentro->fresh()->nombre)->toBe('Mesero Exclusivo Centro');
});

test('administrador de sede Norte recibe 403 Forbidden al intentar eliminar un mesero de sede Centro (CA-30)', function () {
    $this->actingAs($this->adminNorte);
    session(['sucursal_id' => $this->sucursalNorte->id]);

    $response = $this->delete(route('admin.meseros.destroy', $this->meseroCentro));

    $response->assertForbidden();
    expect($this->meseroCentro->fresh()->trashed())->toBeFalse();
});

test('politica de sucursal prohibe acceso de admin a sucursal no asignada (CA-30)', function () {
    expect(Gate::forUser($this->adminNorte)->allows('view', $this->sucursalNorte))->toBeTrue();
    expect(Gate::forUser($this->adminNorte)->allows('view', $this->sucursalCentro))->toBeFalse();
});
