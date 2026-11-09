<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Actions\Usuarios\CrearPersonalAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelefonoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class MeseroController extends Controller
{
    public function __construct(
        protected CrearPersonalAction $crearPersonalAction,
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();
        $sucursalIds = $currentUser->sucursalIds();

        $meseros = User::role('mesero')
            ->whereHas('sucursales', fn ($q) => $q->whereIn('sucursales.id', $sucursalIds))
            ->when($request->input('buscar'), function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->paginate(25);

        return Inertia::render('Admin/Meseros/Index', [
            'meseros' => $meseros,
            'filtros' => $request->only(['buscar']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        /** @var User $currentUser */
        $currentUser = $request->user();
        $sucursalId = session('sucursal_id') ?? ($currentUser->sucursalIds()[0] ?? null);

        if (! $sucursalId) {
            abort(403, 'No hay una sucursal activa asignada.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $this->crearPersonalAction->handle([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'password' => $validated['password'],
            'rol' => 'mesero',
            'sucursal_ids' => [(int) $sucursalId],
        ]);

        return back()->with('success', 'Mesero registrado exitosamente.');
    }

    public function update(Request $request, User $mesero): RedirectResponse
    {
        Gate::authorize('update', $mesero);

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mesero->id)],
            'telefono' => ['required', 'string', 'max:30', Rule::unique('users', 'telefono')->ignore($mesero->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $mesero->update($validated);

        return back()->with('success', 'Datos del mesero actualizados.');
    }

    public function destroy(User $mesero): RedirectResponse
    {
        Gate::authorize('delete', $mesero);

        $this->eliminarConValidacionAction->handle($mesero);

        return back()->with('success', 'Mesero enviado a la papelera.');
    }
}
