<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pedido $pedido): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'mesero'])) {
            return $user->tieneAccesoASucursal($pedido->sucursal_id);
        }

        return (int) $pedido->cliente_id === (int) $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model (CA-10).
     */
    public function update(User $user, Pedido $pedido): bool
    {
        if (! $pedido->estaActivo()) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return $user->tieneAccesoASucursal($pedido->sucursal_id);
        }

        if ($user->hasRole('mesero')) {
            return $user->tieneAccesoASucursal($pedido->sucursal_id)
                && (int) $pedido->mesero_id === (int) $user->id
                && $pedido->puedeEditarItems();
        }

        if ($user->hasRole('cliente')) {
            return (int) $pedido->cliente_id === (int) $user->id
                && $pedido->puedeEditarNotas();
        }

        return false;
    }

    /**
     * Determine whether the user can request or resolve cancellation (CA-08, CA-09).
     */
    public function cancel(User $user, Pedido $pedido): bool
    {
        if (! $pedido->estaActivo()) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'mesero'])) {
            return $user->tieneAccesoASucursal($pedido->sucursal_id);
        }

        return (int) $pedido->cliente_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pedido $pedido): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin') && $user->tieneAccesoASucursal($pedido->sucursal_id);
    }
}
