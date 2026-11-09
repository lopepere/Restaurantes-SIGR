<?php

namespace App\Policies;

use App\Models\Espacio;
use App\Models\User;

class EspacioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'mesero']);
    }

    public function view(User $user, Espacio $espacio): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->tieneAccesoASucursal($espacio->sucursal_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Espacio $espacio): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin') && $user->tieneAccesoASucursal($espacio->sucursal_id);
    }

    public function delete(User $user, Espacio $espacio): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin') && $user->tieneAccesoASucursal($espacio->sucursal_id);
    }
}
