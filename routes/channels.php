<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('sucursal.{sucursalId}.pedidos', function ($user, $sucursalId) {
    if (! $user->tieneAccesoASucursal((int) $sucursalId)) {
        return false;
    }

    return $user->hasAnyRole(['super_admin', 'admin', 'mesero']);
});

Broadcast::channel('cliente.{clienteId}.pedidos', function ($user, $clienteId) {
    return (int) $user->id === (int) $clienteId;
});

Broadcast::channel('sucursal.{sucursalId}.reservas', function ($user, $sucursalId) {
    if (! $user->tieneAccesoASucursal((int) $sucursalId)) {
        return false;
    }

    return $user->hasAnyRole(['super_admin', 'admin', 'mesero']);
});

Broadcast::channel('cliente.{clienteId}.reservas', function ($user, $clienteId) {
    return (int) $user->id === (int) $clienteId;
});
