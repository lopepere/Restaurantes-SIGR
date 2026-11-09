<?php

namespace App\Actions\Usuarios;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EliminarUsuarioAction
{
    public function __construct(
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    /**
     * Elimina lógicamente un usuario, protegiendo al último superadministrador activo (RF-USR-11, RN-USR-08).
     */
    public function handle(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            $superAdminsActivos = User::role('super_admin')->where('activo', true)->count();
            if ($superAdminsActivos <= 1) {
                throw ValidationException::withMessages([
                    'error' => 'No se puede desactivar ni eliminar al último superadministrador activo.',
                ]);
            }
        }

        return $this->eliminarConValidacionAction->handle($user);
    }
}
