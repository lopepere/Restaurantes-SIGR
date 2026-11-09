<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\User;

class SucursalPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Sucursal $sucursal): bool
    {
        return $user->tieneAccesoASucursal($sucursal);
    }

    public function create(User $user): bool
    {
        return false; // Solo super_admin (manejado en before)
    }

    public function update(User $user, Sucursal $sucursal): bool
    {
        return false; // Solo super_admin
    }

    public function delete(User $user, Sucursal $sucursal): bool
    {
        return false; // Solo super_admin
    }
}
