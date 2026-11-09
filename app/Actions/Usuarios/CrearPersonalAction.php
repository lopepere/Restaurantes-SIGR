<?php

namespace App\Actions\Usuarios;

use App\Enums\OrigenUsuario;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\TelefonoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CrearPersonalAction
{
    /**
     * Crea un usuario operativo o administrativo (mesero, admin) (RF-USR-09, RF-USR-10, RN-USR-06, RN-USR-07).
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, password: string, rol: string, sucursal_ids?: array<int>}  $datos
     */
    public function handle(array $datos): User
    {
        $email = strtolower(trim($datos['email']));
        $telefono = TelefonoService::normalizar($datos['telefono']);

        // Unicidad estricta incluyendo eliminados lógicamente (DE-05, RN-USR-02, CA-03)
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya está registrado.',
            ]);
        }

        if (User::withTrashed()->where('telefono', $telefono)->exists()) {
            throw ValidationException::withMessages([
                'telefono' => 'El teléfono ya está registrado.',
            ]);
        }

        $avatar = AvatarService::generar($datos['nombre']);

        $user = User::create([
            'nombre' => $datos['nombre'],
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $datos['direccion'],
            'password' => Hash::make($datos['password']),
            'avatar' => $avatar,
            'idioma' => 'es',
            'activo' => true,
            'origen' => OrigenUsuario::PERSONAL,
            'email_verified_at' => now(), // El personal administrativo se activa al crearse
        ]);

        $rol = $datos['rol'];
        $sucursalIds = $datos['sucursal_ids'] ?? [];

        if (! empty($sucursalIds)) {
            $user->sucursales()->sync($sucursalIds);

            // Asignar rol con equipo Spatie (team = sucursal)
            foreach ($sucursalIds as $sucursalId) {
                setPermissionsTeamId($sucursalId);
                $user->assignRole($rol);
            }
        } else {
            // Rol global (ej. super_admin)
            setPermissionsTeamId(null);
            $user->assignRole($rol);
        }

        return $user;
    }
}
