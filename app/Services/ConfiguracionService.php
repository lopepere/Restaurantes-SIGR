<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Cache;

class ConfiguracionService
{
    /**
     * Obtiene el valor de una configuración: busca primero a nivel de sucursal y si no existe, busca el valor global.
     */
    public static function get(string $clave, int|Sucursal|null $sucursal = null, mixed $default = null): mixed
    {
        $sucursalId = $sucursal instanceof Sucursal ? $sucursal->id : $sucursal;

        if ($sucursalId !== null) {
            $cacheKey = "cfg_s_{$sucursalId}_{$clave}";
            $valorSucursal = Cache::remember($cacheKey, 3600, function () use ($sucursalId, $clave) {
                return Configuracion::where('sucursal_id', $sucursalId)
                    ->where('clave', $clave)
                    ->value('valor');
            });

            if ($valorSucursal !== null) {
                return $valorSucursal;
            }
        }

        // Buscar valor global (sucursal_id = null)
        $cacheKeyGlobal = "cfg_global_{$clave}";
        $valorGlobal = Cache::remember($cacheKeyGlobal, 3600, function () use ($clave) {
            return Configuracion::whereNull('sucursal_id')
                ->where('clave', $clave)
                ->value('valor');
        });

        return $valorGlobal !== null ? $valorGlobal : $default;
    }

    /**
     * Guarda o actualiza una configuración a nivel de sucursal o global, invalidando la caché.
     */
    public static function set(string $clave, mixed $valor, int|Sucursal|null $sucursal = null): Configuracion
    {
        $sucursalId = $sucursal instanceof Sucursal ? $sucursal->id : $sucursal;

        $configuracion = Configuracion::updateOrCreate(
            [
                'sucursal_id' => $sucursalId,
                'clave' => $clave,
            ],
            [
                'valor' => $valor,
            ]
        );

        self::forgetCache($clave, $sucursalId);

        return $configuracion;
    }

    /**
     * Invalida la caché de una clave.
     */
    public static function forgetCache(string $clave, ?int $sucursalId = null): void
    {
        if ($sucursalId !== null) {
            Cache::forget("cfg_s_{$sucursalId}_{$clave}");
        } else {
            Cache::forget("cfg_global_{$clave}");
        }
    }
}
