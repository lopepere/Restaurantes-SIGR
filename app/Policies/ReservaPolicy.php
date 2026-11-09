<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reserva $reserva): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'mesero'])) {
            return $user->tieneAccesoASucursal($reserva->sucursal_id);
        }

        return (int) $reserva->cliente_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reserva $reserva): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return $user->tieneAccesoASucursal($reserva->sucursal_id);
        }

        return (int) $reserva->cliente_id === (int) $user->id && $reserva->estaActiva();
    }

    public function cancel(User $user, Reserva $reserva): bool
    {
        if (! $reserva->estaActiva()) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            return $user->tieneAccesoASucursal($reserva->sucursal_id);
        }

        return (int) $reserva->cliente_id === (int) $user->id;
    }

    public function delete(User $user, Reserva $reserva): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin') && $user->tieneAccesoASucursal($reserva->sucursal_id);
    }
}
