<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator;

class PapeleraService
{
    /**
     * Lista los registros eliminados lógicamente de un modelo con paginación del servidor (RNF-REN-03).
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function listar(string $modelClass, ?int $sucursalId = null, int $perPage = 25): LengthAwarePaginator
    {
        /** @var Builder<TModel> $query */
        $query = $modelClass::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull((new $modelClass)->qualifyColumn('deleted_at'));

        if ($sucursalId !== null && in_array('sucursal_id', (new $modelClass)->getFillable(), true)) {
            $query->where('sucursal_id', $sucursalId);
        }

        /** @var LengthAwarePaginator<int, TModel> $paginated */
        $paginated = $query->latest('deleted_at')->paginate($perPage);

        return $paginated;
    }

    /**
     * Restaura un registro eliminado lógicamente (CA-21, RF-AUD-04).
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    public static function restaurar(string $modelClass, int|string $id): Model
    {
        /** @var Builder<TModel> $query */
        $query = $modelClass::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull((new $modelClass)->qualifyColumn('deleted_at'));

        /** @var TModel $model */
        $model = $query->whereKey($id)->firstOrFail();

        if (method_exists($model, 'restore')) {
            $model->restore();
        }

        return $model;
    }
}
