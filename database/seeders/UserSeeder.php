<?php

namespace Database\Seeders;

use App\Enums\OrigenUsuario;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalCentro = Sucursal::where('slug', 'sede-centro')->first();

        // 1. Super Administrador (Acceso global)
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@sigr.test'],
            [
                'nombre' => 'Super Administrador SIGR',
                'telefono' => '+573009990001',
                'direccion' => 'Sede Principal, Medellín',
                'password' => Hash::make('password'),
                'avatar' => AvatarService::generar('Super Admin'),
                'idioma' => 'es',
                'activo' => true,
                'origen' => OrigenUsuario::PERSONAL,
                'email_verified_at' => now(),
                'politica_aceptada_at' => now(),
            ]
        );
        $superadmin->assignRole('super_admin');

        // 2. Administrador de Sucursal
        $admin = User::firstOrCreate(
            ['email' => 'admin@sigr.test'],
            [
                'nombre' => 'Administrador Sede Centro',
                'telefono' => '+573009990002',
                'direccion' => 'Calle 50 # 45-67, Medellín',
                'password' => Hash::make('password'),
                'avatar' => AvatarService::generar('Admin Centro'),
                'idioma' => 'es',
                'activo' => true,
                'origen' => OrigenUsuario::PERSONAL,
                'email_verified_at' => now(),
                'politica_aceptada_at' => now(),
            ]
        );
        if ($sucursalCentro) {
            $admin->sucursales()->syncWithoutDetaching([$sucursalCentro->id]);
            setPermissionsTeamId($sucursalCentro->id);
            $admin->assignRole('admin');
        }

        // 3. Mesero (con teléfono para CA-03)
        $mesero = User::firstOrCreate(
            ['email' => 'mesero@sigr.test'],
            [
                'nombre' => 'Carlos Mesero',
                'telefono' => '+573001112233',
                'direccion' => 'Carrera 70 # 32-10, Medellín',
                'password' => Hash::make('password'),
                'avatar' => AvatarService::generar('Carlos Mesero'),
                'idioma' => 'es',
                'activo' => true,
                'origen' => OrigenUsuario::PERSONAL,
                'email_verified_at' => now(),
                'politica_aceptada_at' => now(),
            ]
        );
        if ($sucursalCentro) {
            $mesero->sucursales()->syncWithoutDetaching([$sucursalCentro->id]);
            setPermissionsTeamId($sucursalCentro->id);
            $mesero->assignRole('mesero');
        }

        // 4. Cliente creado a partir de Pedido
        $cliente = User::firstOrCreate(
            ['email' => 'cliente@sigr.test'],
            [
                'nombre' => 'Ana Gómez',
                'telefono' => '+573002223344',
                'direccion' => 'Avenida Nutibara # 12-45, Medellín',
                'password' => Hash::make('password'),
                'avatar' => AvatarService::generar('Ana Gómez'),
                'idioma' => 'es',
                'activo' => true,
                'origen' => OrigenUsuario::PEDIDO,
                'invitacion_aceptada_at' => now(),
                'politica_aceptada_at' => now(),
            ]
        );
        $cliente->assignRole('cliente');

        // 5. Cliente con Registro Voluntario y Email Verificado (SC-01)
        $registrado = User::firstOrCreate(
            ['email' => 'registrado@sigr.test'],
            [
                'nombre' => 'David Registrado',
                'telefono' => '+573003334455',
                'direccion' => 'Circular 4 # 73-20, Medellín',
                'password' => Hash::make('password'),
                'avatar' => AvatarService::generar('David Registrado'),
                'idioma' => 'es',
                'activo' => true,
                'origen' => OrigenUsuario::REGISTRO,
                'email_verified_at' => now(),
                'politica_aceptada_at' => now(),
            ]
        );
        $registrado->assignRole('cliente');
    }
}
