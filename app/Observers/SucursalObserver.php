<?php

namespace App\Observers;

use App\Models\Categoria;
use App\Models\Sucursal;

class SucursalObserver
{
    /**
     * Al crear una nueva sucursal, se crean automáticamente las 4 categorías base del menú (DE-07, RF-MEN-01).
     */
    public function created(Sucursal $sucursal): void
    {
        $categoriasBase = [
            [
                'nombre' => 'Entradas',
                'slug' => 'entradas',
                'descripcion' => 'Aperitivos y entradas tradicionales.',
                'orden' => 1,
                'icono' => 'appetizer',
            ],
            [
                'nombre' => 'Platos Fuertes',
                'slug' => 'platos-fuertes',
                'descripcion' => 'Platos principales y especialidades de la casa.',
                'orden' => 2,
                'icono' => 'main-dish',
            ],
            [
                'nombre' => 'Bebidas',
                'slug' => 'bebidas',
                'descripcion' => 'Bebidas frías, jugos naturales, cócteles y calientes.',
                'orden' => 3,
                'icono' => 'drink',
            ],
            [
                'nombre' => 'Postres',
                'slug' => 'postres',
                'descripcion' => 'Postres artesanales y delicias dulces.',
                'orden' => 4,
                'icono' => 'dessert',
            ],
        ];

        foreach ($categoriasBase as $cat) {
            Categoria::create([
                'sucursal_id' => $sucursal->id,
                'nombre' => $cat['nombre'],
                'slug' => $cat['slug'],
                'descripcion' => $cat['descripcion'],
                'orden' => $cat['orden'],
                'icono' => $cat['icono'],
                'activa' => true,
            ]);
        }
    }
}
