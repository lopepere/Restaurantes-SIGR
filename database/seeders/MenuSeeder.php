<?php

namespace Database\Seeders;

use App\Enums\TipoBeneficioPromocion;
use App\Enums\TipoImpuesto;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\ItemMenu;
use App\Models\Promocion;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $sucursal = Sucursal::where('slug', 'sede-centro')->first() ?? Sucursal::first();

        if (! $sucursal) {
            return;
        }

        // Obtener o crear categorías base
        $entradas = Categoria::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'entradas'],
            ['nombre' => 'Entradas', 'descripcion' => 'Aperitivos y entradas tradicionales.', 'orden' => 1, 'activa' => true]
        );

        $platosFuertes = Categoria::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'platos-fuertes'],
            ['nombre' => 'Platos Fuertes', 'descripcion' => 'Platos principales y especialidades de la casa.', 'orden' => 2, 'activa' => true]
        );

        $bebidas = Categoria::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'bebidas'],
            ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frías, jugos naturales y calientes.', 'orden' => 3, 'activa' => true]
        );

        $postres = Categoria::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'postres'],
            ['nombre' => 'Postres', 'descripcion' => 'Postres artesanales y menú infantil.', 'orden' => 4, 'activa' => true]
        );

        // 1. Entradas (5 productos de A.8)
        $itemsEntradas = [
            ['nombre' => 'Patacones con hogao', 'precio' => 14000.00, 'desc' => 'Patacones crocantes acompañados de hogao criollo.'],
            ['nombre' => 'Empanadas x3', 'precio' => 12000.00, 'desc' => 'Tres empanadas de carne y papa con ají casero.'],
            ['nombre' => 'Ceviche de camarón', 'precio' => 26000.00, 'desc' => 'Camarones frescos con cebolla morada, limón y cilantro.'],
            ['nombre' => 'Arepitas con suero', 'precio' => 11000.00, 'desc' => 'Arepitas de maíz blanco con suero costeño.'],
            ['nombre' => 'Sopa de tortilla', 'precio' => 15000.00, 'desc' => 'Caldo tradicional con tiras de tortilla, aguacate y queso.'],
        ];

        foreach ($itemsEntradas as $idx => $item) {
            ItemMenu::withoutGlobalScopes()->firstOrCreate(
                ['sucursal_id' => $sucursal->id, 'slug' => Str::slug($item['nombre'])],
                [
                    'categoria_id' => $entradas->id,
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['desc'],
                    'precio' => $item['precio'],
                    'impuesto_tipo' => TipoImpuesto::INC,
                    'impuesto_porcentaje' => 8.00,
                    'orden' => $idx + 1,
                    'es_combo' => false,
                    'controla_inventario' => true,
                    'existencia' => 50,
                    'existencia_minima' => 5,
                    'disponible' => true,
                    'activo' => true,
                ]
            );
        }

        // 2. Platos Fuertes (5 productos de A.8)
        $itemsPlatos = [
            ['nombre' => 'Bandeja paisa', 'precio' => 38000.00, 'desc' => 'Frijoles, arroz, carne molida, chicharrón, huevo, chorizo, arepa y aguacate.'],
            ['nombre' => 'Pescado frito con arroz de coco', 'precio' => 42000.00, 'desc' => 'Mojarra frita con patacón, ensalada fresca y arroz de coco.'],
            ['nombre' => 'Churrasco 300 g', 'precio' => 46000.00, 'desc' => 'Corte madurado a la brasa con papas a la francesa y ensalada.'],
            ['nombre' => 'Pollo a la plancha', 'precio' => 32400.00, 'desc' => 'Pechuga marinada con especias a la plancha con puré de papa.'],
            ['nombre' => 'Pasta marinera', 'precio' => 39000.00, 'desc' => 'Fetuccini con frutos del mar en salsa de ajo y vino blanco.'],
        ];

        $polloItem = null;
        foreach ($itemsPlatos as $idx => $item) {
            $created = ItemMenu::withoutGlobalScopes()->firstOrCreate(
                ['sucursal_id' => $sucursal->id, 'slug' => Str::slug($item['nombre'])],
                [
                    'categoria_id' => $platosFuertes->id,
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['desc'],
                    'precio' => $item['precio'],
                    'impuesto_tipo' => TipoImpuesto::INC,
                    'impuesto_porcentaje' => 8.00,
                    'orden' => $idx + 1,
                    'es_combo' => false,
                    'controla_inventario' => true,
                    'existencia' => 30,
                    'existencia_minima' => 5,
                    'disponible' => true,
                    'activo' => true,
                ]
            );

            if ($item['nombre'] === 'Pollo a la plancha') {
                $polloItem = $created;
            }
        }

        // 3. Bebidas (5 productos de A.8)
        $itemsBebidas = [
            ['nombre' => 'Limonada de coco', 'precio' => 12000.00, 'desc' => 'Cremosa limonada con leche de coco y hielo granizado.'],
            ['nombre' => 'Jugo natural', 'precio' => 8000.00, 'desc' => 'En agua o leche: mango, maracuyá, lulo o guanábana.'],
            ['nombre' => 'Gaseosa 400 ml', 'precio' => 6000.00, 'desc' => 'Refrescante bebida gaseosa.'],
            ['nombre' => 'Agua 600 ml', 'precio' => 4500.00, 'desc' => 'Agua mineral con o sin gas.'],
            ['nombre' => 'Café americano', 'precio' => 5000.00, 'desc' => 'Café 100% colombiano de origen.'],
        ];

        $limonadaItem = null;
        foreach ($itemsBebidas as $idx => $item) {
            $created = ItemMenu::withoutGlobalScopes()->firstOrCreate(
                ['sucursal_id' => $sucursal->id, 'slug' => Str::slug($item['nombre'])],
                [
                    'categoria_id' => $bebidas->id,
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['desc'],
                    'precio' => $item['precio'],
                    'impuesto_tipo' => TipoImpuesto::INC,
                    'impuesto_porcentaje' => 8.00,
                    'orden' => $idx + 1,
                    'es_combo' => false,
                    'controla_inventario' => true,
                    'existencia' => 100,
                    'existencia_minima' => 10,
                    'disponible' => true,
                    'activo' => true,
                ]
            );

            if ($item['nombre'] === 'Limonada de coco') {
                $limonadaItem = $created;
            }
        }

        // 4. Postres / Menú Infantil (5 productos de A.8)
        $itemsInfantiles = [
            ['nombre' => 'Nuggets con papas', 'precio' => 19000.00, 'desc' => 'Nuggets de pollo crocantes con papas fritas y salsas.'],
            ['nombre' => 'Mini hamburguesa', 'precio' => 21000.00, 'desc' => 'Carne 100 g con queso cheddar y papitas.'],
            ['nombre' => 'Pasta con queso', 'precio' => 18000.00, 'desc' => 'Pasta corta con suave salsa de queso fundido.'],
            ['nombre' => 'Salchipapa infantil', 'precio' => 17000.00, 'desc' => 'Salchicha seleccionada con papas a la francesa.'],
            ['nombre' => 'Jugo pequeño', 'precio' => 5000.00, 'desc' => 'Vaso infantil de jugo de frutas.'],
        ];

        foreach ($itemsInfantiles as $idx => $item) {
            ItemMenu::withoutGlobalScopes()->firstOrCreate(
                ['sucursal_id' => $sucursal->id, 'slug' => Str::slug($item['nombre'])],
                [
                    'categoria_id' => $postres->id,
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['desc'],
                    'precio' => $item['precio'],
                    'impuesto_tipo' => TipoImpuesto::INC,
                    'impuesto_porcentaje' => 8.00,
                    'orden' => $idx + 1,
                    'es_combo' => false,
                    'controla_inventario' => true,
                    'existencia' => 20,
                    'existencia_minima' => 5,
                    'disponible' => true,
                    'activo' => true,
                ]
            );
        }

        // 5. «Combo Ejecutivo» (plato principal + bebida) de A.8
        $comboEjecutivo = ItemMenu::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'combo-ejecutivo'],
            [
                'categoria_id' => $platosFuertes->id,
                'nombre' => 'Combo Ejecutivo',
                'descripcion' => 'Pollo a la plancha con puré de papa + Limonada de coco.',
                'precio' => 36000.00,
                'impuesto_tipo' => TipoImpuesto::INC,
                'impuesto_porcentaje' => 8.00,
                'orden' => 99,
                'es_combo' => true,
                'controla_inventario' => false,
                'existencia' => 0,
                'existencia_minima' => 0,
                'disponible' => true,
                'activo' => true,
            ]
        );

        if ($polloItem && $limonadaItem) {
            ComboItem::firstOrCreate([
                'combo_id' => $comboEjecutivo->id,
                'item_id' => $polloItem->id,
            ], ['cantidad' => 1.00]);

            ComboItem::firstOrCreate([
                'combo_id' => $comboEjecutivo->id,
                'item_id' => $limonadaItem->id,
            ], ['cantidad' => 1.00]);
        }

        // 6. Promoción vigente (A.8)
        $promocion = Promocion::withoutGlobalScopes()->firstOrCreate(
            ['sucursal_id' => $sucursal->id, 'slug' => 'descuento-entradas-15'],
            [
                'nombre' => '15% OFF en Todas las Entradas',
                'descripcion' => 'Aprovecha este mes un 15% de descuento directo en toda nuestra sección de aperitivos y entradas.',
                'tipo_beneficio' => TipoBeneficioPromocion::PORCENTAJE,
                'valor_beneficio' => 15.00,
                'fecha_inicio' => now()->subDays(5),
                'fecha_fin' => now()->addDays(25),
                'dias_semana' => [0, 1, 2, 3, 4, 5, 6],
                'activa' => true,
                'publicada' => true,
                'aplica_a_todo' => false,
            ]
        );

        $promocion->categorias()->syncWithoutDetaching([$entradas->id]);
    }
}
