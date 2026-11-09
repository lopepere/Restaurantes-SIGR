<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        Sucursal::firstOrCreate(
            ['slug' => 'sede-centro'],
            [
                'nombre' => 'Sede Centro',
                'direccion' => 'Calle 50 # 45-67, Medellín, Colombia',
                'telefono' => '+573001234567',
                'zona_horaria' => 'America/Bogota',
                'moneda' => 'COP',
                'idioma' => 'es',
                'activa' => true,
            ]
        );

        Sucursal::firstOrCreate(
            ['slug' => 'sede-norte'],
            [
                'nombre' => 'Sede Norte',
                'direccion' => 'Carrera 43A # 1-50, Medellín, Colombia',
                'telefono' => '+573007654321',
                'zona_horaria' => 'America/Bogota',
                'moneda' => 'COP',
                'idioma' => 'es',
                'activa' => true,
            ]
        );
    }
}
