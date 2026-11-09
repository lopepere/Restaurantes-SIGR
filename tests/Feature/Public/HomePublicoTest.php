<?php

use App\Models\Sucursal;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->sucursalCentro = Sucursal::create([
        'nombre' => 'Sede Centro',
        'slug' => 'sede-centro',
        'direccion' => 'Calle 10 # 4-20',
        'telefono' => '+573001234567',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->sucursalNorte = Sucursal::create([
        'nombre' => 'Sede Norte',
        'slug' => 'sede-norte',
        'direccion' => 'Calle 150 # 15-30',
        'telefono' => '+573007654321',
        'zona_horaria' => 'America/Bogota',
        'moneda' => 'COP',
        'idioma' => 'es',
        'activa' => true,
    ]);

    $this->seed(MenuSeeder::class);
});

test('visitante visualiza el home con selector de sucursal, promociones y catalogo de menu (RF-PUB-01..05)', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home')
        ->has('sucursales', 2)
        ->where('sucursalActiva.slug', 'sede-centro')
        ->has('menu.categorias')
        ->has('menu.promociones')
    );
});

test('visitante puede cambiar de sede mediante url /sucursal/{slug} fijandola en sesion (RF-PUB-02)', function () {
    $response = $this->get('/sucursal/sede-norte');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home')
        ->where('sucursalActiva.slug', 'sede-norte')
    );

    expect(session('sucursal_id'))->toBe($this->sucursalNorte->id);
});

test('buscador en vivo filtra items del menu por nombre o descripcion (RF-PUB-05)', function () {
    $response = $this->get('/?buscar=patacones');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Public/Home')
        ->has('menu.categorias', 1)
        ->where('menu.categorias.0.items.0.nombre', 'Patacones con hogao')
    );
});
