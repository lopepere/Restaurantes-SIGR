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
                if ($user->hasRole('super_admin')) {
                    return;
                }

                // Los clientes acceden a sus propios pedidos, reservas y comprobantes independientemente de sucursal_user
                if ($user->hasRole('cliente')) {
                    $tabla = $q->getModel()->getTable();
                    if (in_array($tabla, ['pedidos', 'reservas', 'comprobantes'], true)) {
                        $q->where($tabla.'.cliente_id', $user->id);
                    }

                    return;
                }

                $sucursalIds = $user->sucursalIds();
                $q->whereIn($q->getModel()->getTable().'.sucursal_id', $sucursalIds);
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

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Scope para consultar registros de una sucursal específica sin interferencia del global scope.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeParaSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->withoutGlobalScope('sucursal')->where($this->getTable().'.sucursal_id', $sucursalId);
    }
}
