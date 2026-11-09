<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\PapeleraService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PapeleraController extends Controller
{
    /**
     * Mapeo de módulos admitidos en la papelera general.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $modelosPermitidos = [
        'meseros' => User::class,
        'sucursales' => Sucursal::class,
    ];

    public function index(Request $request): Response
    {
        $modulo = $request->input('modulo', 'meseros');
        $modelClass = $this->modelosPermitidos[$modulo] ?? User::class;

        $registros = PapeleraService::listar($modelClass);

        return Inertia::render('Admin/Papelera/Index', [
            'modulo' => $modulo,
            'modulosDisponibles' => array_keys($this->modelosPermitidos),
            'registros' => $registros,
        ]);
    }

    public function restore(Request $request, string $modulo, int|string $id): RedirectResponse
    {
        if (! isset($this->modelosPermitidos[$modulo])) {
            abort(404, 'Módulo no soportado en papelera.');
        }

        $modelClass = $this->modelosPermitidos[$modulo];
        PapeleraService::restaurar($modelClass, $id);

        return back()->with('success', 'Registro restaurado correctamente desde la papelera.');
    }
}
