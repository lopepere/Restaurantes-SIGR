<?php

namespace App\Policies;

use App\Models\ItemMenu;
use App\Models\User;

class ItemMenuPolicy
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

    public function view(User $user, ItemMenu $itemMenu): bool
    {
        return in_array($itemMenu->sucursal_id, $user->sucursalIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ItemMenu $itemMenu): bool
    {
        return $user->hasRole('admin') && in_array($itemMenu->sucursal_id, $user->sucursalIds(), true);
    }

    public function delete(User $user, ItemMenu $itemMenu): bool
    {
        return $user->hasRole('admin') && in_array($itemMenu->sucursal_id, $user->sucursalIds(), true);
    }
}
