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
        $sucursalNorte = Sucursal::where('slug', 'sede-norte')->first();

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

        // 2. Administrador de Sucursal Centro (admin.centro@sigr.test y admin@sigr.test)
        $adminUsers = [
            ['email' => 'admin@sigr.test', 'telefono' => '+573009990002'],
            ['email' => 'admin.centro@sigr.test', 'telefono' => '+573009990021'],
        ];
        foreach ($adminUsers as $adminData) {
            $admin = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'nombre' => 'Administrador Sede Centro',
                    'telefono' => $adminData['telefono'],
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
        }

        // 2b. Administrador de Sucursal Norte
        if ($sucursalNorte) {
            $adminNorte = User::firstOrCreate(
                ['email' => 'admin.norte@sigr.test'],
                [
                    'nombre' => 'Administrador Sede Norte',
                    'telefono' => '+573009990003',
                    'direccion' => 'Carrera 43A # 1-50, Medellín',
                    'password' => Hash::make('password'),
                    'avatar' => AvatarService::generar('Admin Norte'),
                    'idioma' => 'es',
                    'activo' => true,
                    'origen' => OrigenUsuario::PERSONAL,
                    'email_verified_at' => now(),
                    'politica_aceptada_at' => now(),
                ]
            );
            $adminNorte->sucursales()->syncWithoutDetaching([$sucursalNorte->id]);
            setPermissionsTeamId($sucursalNorte->id);
            $adminNorte->assignRole('admin');
        }

        // 3. Mesero (mesero.centro@sigr.test y mesero@sigr.test)
        $meseroUsers = [
            ['email' => 'mesero@sigr.test', 'telefono' => '+573001112233'],
            ['email' => 'mesero.centro@sigr.test', 'telefono' => '+573001112235'],
        ];
        foreach ($meseroUsers as $meseroData) {
            $mesero = User::firstOrCreate(
                ['email' => $meseroData['email']],
                [
                    'nombre' => 'Carlos Mesero',
                    'telefono' => $meseroData['telefono'],
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
