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
     * Alias de get() en español para consistencia lingüística.
     */
    public static function obtener(string $clave, int|Sucursal|null $sucursal = null, mixed $default = null): mixed
    {
        return self::get($clave, $sucursal, $default);
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

    /**
     * Retorna el catálogo dinámico de impuestos configurados en el sistema (Item 1).
     *
     * @return array<int, array{id: string, codigo: string, nombre: string, porcentaje: float, descripcion: string, activo: bool}>
     */
    public static function catalogoImpuestos(): array
    {
        $raw = self::get('impuestos.catalogo');
        if (is_array($raw)) {
            /** @var array<int, array{id: string, codigo: string, nombre: string, porcentaje: float, descripcion: string, activo: bool}> $raw */
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                /** @var array<int, array{id: string, codigo: string, nombre: string, porcentaje: float, descripcion: string, activo: bool}> $decoded */
                return $decoded;
            }
        }

        // Catálogo base por defecto (Colombia)
        return [
            [
                'id' => 'inc_8',
                'codigo' => 'INC',
                'nombre' => 'Impuesto Nacional al Consumo (INC)',
                'porcentaje' => 8.0,
                'descripcion' => 'Tarifa general de comidas y bebidas en restaurantes',
                'activo' => true,
            ],
            [
                'id' => 'iva_19',
                'codigo' => 'IVA_19',
                'nombre' => 'Impuesto al Valor Agregado (IVA 19%)',
                'porcentaje' => 19.0,
                'descripcion' => 'Tarifa general para productos gravados con IVA',
                'activo' => true,
            ],
            [
                'id' => 'iva_5',
                'codigo' => 'IVA_5',
                'nombre' => 'IVA Reducido (5%)',
                'porcentaje' => 5.0,
                'descripcion' => 'Tarifa diferencial para insumos y productos específicos',
                'activo' => true,
            ],
            [
                'id' => 'exento_0',
                'codigo' => 'EXENTO',
                'nombre' => 'Exento / Excluido (0%)',
                'porcentaje' => 0.0,
                'descripcion' => 'Productos exentos o excluidos de gravámenes',
                'activo' => true,
            ],
        ];
    }

    /**
     * Guarda el catálogo dinámico de impuestos en la configuración global.
     *
     * @param  array<int, array<string, mixed>>  $catalogo
     */
    public static function guardarCatalogoImpuestos(array $catalogo): void
    {
        self::set('impuestos.catalogo', json_encode(array_values($catalogo)));
    }
}
