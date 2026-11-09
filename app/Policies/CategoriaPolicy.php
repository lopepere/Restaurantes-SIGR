<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
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
        return $user->hasRole(['admin', 'mesero']);
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return in_array($categoria->sucursal_id, $user->sucursalIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->hasRole('admin') && in_array($categoria->sucursal_id, $user->sucursalIds(), true);
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->hasRole('admin') && in_array($categoria->sucursal_id, $user->sucursalIds(), true);
    }
}
