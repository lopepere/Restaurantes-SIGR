<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'indicadores' => [
                'ventas_hoy' => 0.00,
                'pedidos_hoy' => 0,
                'reservas_hoy' => 0,
                'cancelaciones_pendientes' => 0,
            ],
        ]);
    }
}
