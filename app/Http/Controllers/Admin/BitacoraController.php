<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class BitacoraController extends Controller
{
    /**
     * Vista de bitácora de solo lectura con filtros (DE-12, RF-AUD-02, CA-19).
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $query = Activity::query()
            ->with(['causer'])
            ->latest('created_at');

        // Si no es superadmin, restringir a las actividades de sus sucursales asignadas
        if (! $user->hasRole('super_admin')) {
            $sucursalIds = $user->sucursalIds();
            $query->whereIn('sucursal_id', $sucursalIds);
        }

        // Filtros
        if ($request->filled('modulo')) {
            $query->where('log_name', $request->input('modulo'));
        }

        if ($request->filled('usuario_id')) {
            $query->where('causer_id', $request->input('usuario_id'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $actividades = $query->paginate(25);

        return Inertia::render('Admin/Bitacora/Index', [
            'actividades' => $actividades,
            'filtros' => $request->only(['modulo', 'usuario_id', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Bloqueo inmutable estricto: la bitácora no puede modificarse ni eliminarse (RN-GEN-07, RF-AUD-03, CA-19).
     */
    public function update(): never
    {
        abort(403, 'La bitácora de auditoría es inmutable y no puede ser modificada.');
    }

    public function destroy(): never
    {
        abort(403, 'La bitácora de auditoría es inmutable y no puede ser eliminada.');
    }
}
