<?php

use App\Actions\Comun\EliminarConValidacionAction;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\PapeleraService;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursal = Sucursal::create([
        'nombre' => 'Sede Auditoria',
        'slug' => 'sede-auditoria',
        'direccion' => 'Calle 50 # 10-20',
        'telefono' => '+573001234567',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->admin = User::create([
        'nombre' => 'Admin Auditoria',
        'email' => 'admin.audit@sigr.test',
        'telefono' => '+573002222333',
        'direccion' => 'Calle 50',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $this->admin->assignRole('admin');
    $this->admin->sucursales()->attach($this->sucursal->id);
});

test('edicion de una entidad auditable registra usuario, valores viejos/nuevos, ip y sucursal (CA-19, RF-AUD-01, 02)', function () {
    $this->actingAs($this->admin);
    session(['sucursal_id' => $this->sucursal->id]);

    $mesero = User::create([
        'nombre' => 'Mesero Original',
        'email' => 'mesero.audit@sigr.test',
        'telefono' => '+573004445566',
        'direccion' => 'Calle Original',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);
    $mesero->assignRole('mesero');
    $mesero->sucursales()->attach($this->sucursal->id);

    // Limpiar logs iniciales de creación
    Activity::truncate();

    // Actualizar datos del mesero mediante petición HTTP para capturar IP y User-Agent
    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '192.168.1.50',
        'HTTP_USER_AGENT' => 'TestBrowser/1.0',
    ])->put(route('admin.meseros.update', $mesero), [
        'nombre' => 'Mesero Actualizado',
        'email' => $mesero->email,
        'telefono' => $mesero->telefono,
        'direccion' => 'Calle Nueva 123',
        'activo' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $log = Activity::where('subject_type', User::class)
        ->where('subject_id', $mesero->id)
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($this->admin->id)
        ->and($log->attribute_changes['old']['nombre'])->toBe('Mesero Original')
        ->and($log->attribute_changes['attributes']['nombre'])->toBe('Mesero Actualizado')
        ->and($log->attribute_changes['old']['direccion'])->toBe('Calle Original')
        ->and($log->attribute_changes['attributes']['direccion'])->toBe('Calle Nueva 123')
        ->and($log->ip)->toBe('192.168.1.50')
        ->and($log->user_agent)->toBe('TestBrowser/1.0')
        ->and($log->sucursal_id)->toBe($this->sucursal->id)
        ->and($log->created_at)->not->toBeNull();
});

test('bitacora de auditoria es inmutable: update y destroy retornan 403 (RF-AUD-03, RN-GEN-07, CA-19)', function () {
    $this->actingAs($this->admin);

    $putResponse = $this->put('/admin/bitacora/1', ['descripcion' => 'alterado']);
    $putResponse->assertForbidden();

    $deleteResponse = $this->delete('/admin/bitacora/1');
    $deleteResponse->assertForbidden();
});

test('entidad eliminada logicamente puede restaurarse desde la papelera (CA-21, RF-AUD-04)', function () {
    $mesero = User::create([
        'nombre' => 'Mesero A Borrar',
        'email' => 'mesero.borrar@sigr.test',
        'telefono' => '+573007778899',
        'direccion' => 'Calle Borrar',
        'password' => Hash::make('Password123!', ['rounds' => 4]),
        'activo' => true,
        'email_verified_at' => now(),
    ]);

    $mesero->delete();
    expect($mesero->fresh()->trashed())->toBeTrue();

    // Restaurar a través de PapeleraService
    PapeleraService::restaurar(User::class, $mesero->id);

    expect($mesero->fresh()->trashed())->toBeFalse();
    expect($mesero->fresh()->deleted_at)->toBeNull();

    // Comprobar también restauración vía endpoint HTTP
    $mesero->delete();
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.papelera.restore', [
        'modulo' => 'meseros',
        'id' => $mesero->id,
    ]));

    $response->assertSessionHasNoErrors();
    expect($mesero->fresh()->trashed())->toBeFalse();
});

test('borrado logico de entidad con relaciones activas es rechazado con mensaje descriptivo (RF-AUD-05, RN-GEN-04)', function () {
    $action = app(EliminarConValidacionAction::class);

    // $this->sucursal tiene asociado a $this->admin en sucursal_user
    expect($this->sucursal->users()->count())->toBeGreaterThan(0);

    // Intentar eliminar la sucursal validando la relación 'users'
    try {
        $action->handle($this->sucursal, ['users']);
        $this->fail('Se esperaba ValidationException');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first('error'))
            ->toContain('Tiene 1 users asociados.');
    }

    // La sucursal debe permanecer intacta (no eliminada)
    expect($this->sucursal->fresh()->trashed())->toBeFalse();
});
