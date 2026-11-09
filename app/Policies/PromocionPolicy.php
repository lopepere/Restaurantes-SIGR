<?php

namespace App\Policies;

use App\Models\Promocion;
use App\Models\User;

class PromocionPolicy
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

    public function view(User $user, Promocion $promocion): bool
    {
        return in_array($promocion->sucursal_id, $user->sucursalIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Promocion $promocion): bool
    {
        return $user->hasRole('admin') && in_array($promocion->sucursal_id, $user->sucursalIds(), true);
    }

    public function delete(User $user, Promocion $promocion): bool
    {
        return $user->hasRole('admin') && in_array($promocion->sucursal_id, $user->sucursalIds(), true);
    }
}
