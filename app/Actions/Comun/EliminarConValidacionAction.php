<?php

namespace App\Actions\Comun;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EliminarConValidacionAction
{
    /**
     * Valida relaciones activas antes de permitir el borrado lógico (DE-11, RN-GEN-04, RF-AUD-05).
     *
     * @param  Model  $model  Modelo a eliminar lógicamente
     * @param  array<int, string>  $relaciones  Nombres de los métodos de relación a comprobar
     *
     * @throws ValidationException
     */
    public function handle(Model $model, array $relaciones = []): bool
    {
        $conflictos = [];

        foreach ($relaciones as $relacion) {
            if (method_exists($model, $relacion)) {
                $conteo = $model->{$relacion}()->count();
                if ($conteo > 0) {
                    $conflictos[] = "Tiene {$conteo} {$relacion} asociados.";
                }
            }
        }

        if (! empty($conflictos)) {
            $mensaje = implode(' ', $conflictos);
            throw ValidationException::withMessages([
                'error' => $mensaje,
            ]);
        }

        return (bool) $model->delete();
    }
}
