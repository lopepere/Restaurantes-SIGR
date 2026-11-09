<?php

namespace Database\Seeders;

use App\Enums\EstadoEspacio;
use App\Models\Espacio;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class EspacioSeeder extends Seeder
{
    /**
     * Run the database seeds according to section A.8 of the prompt (12 espacios).
     */
    public function run(): void
    {
        $sucursales = Sucursal::all();

        foreach ($sucursales as $sucursal) {
            $espacios = [
                ['nombre' => 'Barra 1', 'tipo' => 'barra', 'capacidad' => 1],
                ['nombre' => 'Barra 2', 'tipo' => 'barra', 'capacidad' => 1],
                ['nombre' => 'Mesa 1', 'tipo' => 'mesa', 'capacidad' => 2],
                ['nombre' => 'Mesa 2', 'tipo' => 'mesa', 'capacidad' => 2],
                ['nombre' => 'Mesa 3', 'tipo' => 'mesa', 'capacidad' => 4],
                ['nombre' => 'Mesa 4', 'tipo' => 'mesa', 'capacidad' => 4],
                ['nombre' => 'Mesa 5', 'tipo' => 'mesa', 'capacidad' => 4],
                ['nombre' => 'Mesa 6', 'tipo' => 'mesa', 'capacidad' => 4],
                ['nombre' => 'Mesa 7', 'tipo' => 'mesa', 'capacidad' => 6],
                ['nombre' => 'Mesa 8', 'tipo' => 'mesa', 'capacidad' => 6],
                ['nombre' => 'Mesa 9', 'tipo' => 'mesa', 'capacidad' => 8],
                ['nombre' => 'Salón de Eventos', 'tipo' => 'salon', 'capacidad' => 20],
            ];

            foreach ($espacios as $esp) {
                Espacio::firstOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'nombre' => $esp['nombre'],
                    ],
                    [
                        'tipo' => $esp['tipo'],
                        'capacidad' => $esp['capacidad'],
                        'estado' => EstadoEspacio::LIBRE,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}
