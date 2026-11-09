<?php

namespace App\Traits;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSucursal
{
    protected static function bootBelongsToSucursal(): void
    {
        static::addGlobalScope('sucursal', function (Builder $q) {
            $user = auth()->user();

            if ($user instanceof User) {
                // El Superadmin tiene acceso global sin restricciones por sucursal
                if (! $user->hasRole('super_admin')) {
                    $sucursalIds = $user->sucursalIds();
                    $q->whereIn($q->getModel()->getTable().'.sucursal_id', $sucursalIds);
                }
            } else {
                // Para visitantes públicos, filtrar por la sucursal activa en sesión si existe
                $sucursalId = session('sucursal_id');
                if ($sucursalId) {
                    $q->where($q->getModel()->getTable().'.sucursal_id', $sucursalId);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->sucursal_id)) {
                $model->sucursal_id = session('sucursal_id');
            }
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
