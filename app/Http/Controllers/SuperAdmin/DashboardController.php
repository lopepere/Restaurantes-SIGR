<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('SuperAdmin/Dashboard', [
            'totalSucursales' => Sucursal::count(),
            'totalAdministradores' => User::role('admin')->count(),
            'totalMeseros' => User::role('mesero')->count(),
            'totalClientes' => User::role('cliente')->count(),
        ]);
    }
}
