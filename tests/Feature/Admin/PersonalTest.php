<?php

use App\Actions\Usuarios\EliminarUsuarioAction;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    $this->superadmin = User::create([
        'nombre' => 'Super Administrador',
        'email' => 'superadmin@sigr.test',
        'telefono' => '+573001111111',
        'direccion' => 'Central',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->superadmin->assignRole('super_admin');

    $this->adminCentro = User::create([
        'nombre' => 'Admin Centro',
        'email' => 'admin.centro@sigr.test',
        'telefono' => '+573002222222',
        'direccion' => 'Calle 10',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->adminCentro->assignRole('admin');
    $this->adminCentro->sucursales()->attach($this->sucursalCentro->id);
});

test('administrador de sucursal puede registrar un mesero asignado a su sede (RF-USR-09)', function () {
    $this->actingAs($this->adminCentro);
    session(['sucursal_id' => $this->sucursalCentro->id]);

    $response = $this->post(route('admin.meseros.store'), [
        'nombre' => 'Juan Mesero',
        'email' => 'juan.mesero@sigr.test',
        'telefono' => '300 555 4433',
        'direccion' => 'Calle 20 # 5-10',
        'password' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $mesero = User::where('email', 'juan.mesero@sigr.test')->first();
    expect($mesero)->not->toBeNull()
        ->and($mesero->hasRole('mesero'))->toBeTrue()
        ->and($mesero->telefono)->toBe('+573005554433')
        ->and($mesero->sucursales->pluck('id')->all())->toContain($this->sucursalCentro->id);
});

test('superadministrador puede crear un administrador asignado a una o varias sucursales (RF-USR-10)', function () {
    $this->actingAs($this->superadmin);

    $response = $this->post(route('superadmin.administradores.store'), [
        'nombre' => 'Admin Multisede',
        'email' => 'admin.multi@sigr.test',
        'telefono' => '+573008889900',
        'direccion' => 'Avenida Principal # 1',
        'password' => 'Password123!',
        'sucursal_ids' => [$this->sucursalCentro->id, $this->sucursalNorte->id],
    ]);

    $response->assertSessionHasNoErrors();

    $admin = User::where('email', 'admin.multi@sigr.test')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->sucursales->pluck('id')->all())->toEqualCanonicalizing([
            $this->sucursalCentro->id,
            $this->sucursalNorte->id,
        ]);
});

test('sistema bloquea la eliminacion o desactivacion del ultimo superadministrador activo (RF-USR-11, RN-USR-08)', function () {
    // Actualmente solo existe $this->superadmin
    $action = app(EliminarUsuarioAction::class);

    expect(fn () => $action->handle($this->superadmin))
        ->toThrow(ValidationException::class);

    expect(User::role('super_admin')->where('activo', true)->count())->toBe(1);

    // Si se crea un segundo superadministrador activo, ahora sí se puede eliminar el primero
    $segundoSuper = User::create([
        'nombre' => 'Segundo SuperAdmin',
        'email' => 'super2@sigr.test',
        'telefono' => '+573009990011',
        'direccion' => 'Central 2',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $segundoSuper->assignRole('super_admin');

    // Ahora eliminar al primer superadmin debe tener éxito
    $eliminado = $action->handle($this->superadmin);
    expect($eliminado)->toBeTrue();
    expect($this->superadmin->fresh()->trashed())->toBeTrue();
});
