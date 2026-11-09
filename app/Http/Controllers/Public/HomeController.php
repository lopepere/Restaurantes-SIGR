<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected MenuService $menuService
    ) {}

    public function __invoke(Request $request, ?string $slug = null): Response
    {
        $sucursales = Sucursal::where('activa', true)
            ->get(['id', 'nombre', 'slug', 'direccion', 'telefono', 'moneda']);

        if ($sucursales->isEmpty()) {
            abort(503, 'No hay sucursales activas disponibles.');
        }

        // Determinar sucursal activa
        if ($slug) {
            $sucursal = $sucursales->firstWhere('slug', $slug);
            if (! $sucursal) {
                abort(404, 'Sucursal no encontrada.');
            }
            session(['sucursal_id' => $sucursal->id]);
        } else {
            $sesionSucursalId = session('sucursal_id');
            $sucursal = $sucursales->firstWhere('id', $sesionSucursalId) ?? $sucursales->first();
            session(['sucursal_id' => $sucursal->id]);
        }

        $busqueda = $request->input('buscar');
        $menuData = $this->menuService->obtenerMenuPublico($sucursal->id, $busqueda);

        return Inertia::render('Public/Home', [
            'sucursales' => $sucursales,
            'sucursalActiva' => $sucursal,
            'menu' => $menuData,
            'filtros' => [
                'buscar' => $busqueda,
                'categoria' => $request->input('categoria'),
            ],
        ]);
    }
}
