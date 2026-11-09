<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Services\ConfiguracionService;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalCentro = Sucursal::where('slug', 'sede-centro')->first();

        // Parámetros globales por defecto
        ConfiguracionService::set('propina.preseleccionar', false); // SC-02 / DE-28
        ConfiguracionService::set('propina.porcentajes_sugeridos', [5, 10]);
        ConfiguracionService::set('ventana_edicion', 5); // 5 minutos (DE-03)
        ConfiguracionService::set('cancelacion_automatica', false); // DE-04
        ConfiguracionService::set('ocultar_agotados', false); // RN-MEN-05
        ConfiguracionService::set('reserva.duracion_minutos', 120); // DE-20
        ConfiguracionService::set('reserva.intervalo_minutos', 30);
        ConfiguracionService::set('reserva.anticipacion_minima_horas', 2);
        ConfiguracionService::set('reserva.anticipacion_maxima_dias', 60);
        ConfiguracionService::set('reserva.aprobacion_automatica', false);

        if ($sucursalCentro) {
            // Parámetros específicos por sucursal (ejemplo)
            ConfiguracionService::set('nombre_comercial', 'SIGR - Sede Centro Gourmet', $sucursalCentro);
        }
    }
}
