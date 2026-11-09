<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * El superadministrador tiene acceso total a todas las operaciones sobre usuarios.
     */
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

    public function view(User $user, User $model): bool
    {
        // Un usuario siempre puede ver su propio perfil
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->hasRole('admin')) {
            // El admin puede ver usuarios que compartan al menos una sucursal asignada
            $interseccion = array_intersect($user->sucursalIds(), $model->sucursalIds());

            return ! empty($interseccion);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        // El usuario puede editar su propia información básica
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->hasRole('admin')) {
            // El admin solo puede editar meseros de su propia sucursal
            if ($model->hasRole('mesero')) {
                $interseccion = array_intersect($user->sucursalIds(), $model->sucursalIds());

                return ! empty($interseccion);
            }
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false; // No se puede auto-eliminar
        }

        if ($user->hasRole('admin') && $model->hasRole('mesero')) {
            $interseccion = array_intersect($user->sucursalIds(), $model->sucursalIds());

            return ! empty($interseccion);
        }

        return false;
    }
}
