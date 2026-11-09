<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Usuarios\CrearPersonalAction;
use App\Actions\Usuarios\EliminarUsuarioAction;
use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\TelefonoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdministradorController extends Controller
{
    public function __construct(
        protected CrearPersonalAction $crearPersonalAction,
        protected EliminarUsuarioAction $eliminarUsuarioAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $administradores = User::role('admin')
            ->with('sucursales')
            ->when($request->input('buscar'), function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->paginate(25);

        $sucursales = Sucursal::where('activa', true)->get(['id', 'nombre']);

        return Inertia::render('SuperAdmin/Administradores/Index', [
            'administradores' => $administradores,
            'sucursales' => $sucursales,
            'filtros' => $request->only(['buscar']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
            'sucursal_ids' => ['required', 'array', 'min:1'],
            'sucursal_ids.*' => ['integer', 'exists:sucursales,id'],
        ]);

        $this->crearPersonalAction->handle([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'password' => $validated['password'],
            'rol' => 'admin',
            'sucursal_ids' => $validated['sucursal_ids'],
        ]);

        return back()->with('success', 'Administrador creado y asignado.');
    }

    public function update(Request $request, User $administrador): RedirectResponse
    {
        Gate::authorize('update', $administrador);

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrador->id)],
            'telefono' => ['required', 'string', 'max:30', Rule::unique('users', 'telefono')->ignore($administrador->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'sucursal_ids' => ['required', 'array', 'min:1'],
            'sucursal_ids.*' => ['integer', 'exists:sucursales,id'],
        ]);

        $administrador->update([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'activo' => $validated['activo'],
        ]);

        $administrador->sucursales()->sync($validated['sucursal_ids']);

        return back()->with('success', 'Administrador actualizado.');
    }

    public function destroy(User $administrador): RedirectResponse
    {
        Gate::authorize('delete', $administrador);

        $this->eliminarUsuarioAction->handle($administrador);

        return back()->with('success', 'Administrador eliminado lógicamente.');
    }
}
