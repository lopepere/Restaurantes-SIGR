<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Services\TelefonoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SucursalController extends Controller
{
    public function __construct(
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Sucursal::class);

        $sucursales = Sucursal::withCount('users')
            ->when($request->input('buscar'), function ($q, $buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('direccion', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%");
            })
            ->latest()
            ->paginate(25);

        return Inertia::render('SuperAdmin/Sucursales/Index', [
            'sucursales' => $sucursales,
            'filtros' => $request->only(['buscar']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Sucursal::class);

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:sucursales,slug'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'zona_horaria' => ['required', 'string', 'max:50'],
            'moneda' => ['required', 'string', 'max:10'],
            'idioma' => ['required', 'string', 'in:es,en'],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['nombre']);

        Sucursal::create([
            'nombre' => $validated['nombre'],
            'slug' => $slug,
            'direccion' => $validated['direccion'],
            'telefono' => $validated['telefono'],
            'zona_horaria' => $validated['zona_horaria'],
            'moneda' => $validated['moneda'],
            'idioma' => $validated['idioma'],
            'activa' => true,
        ]);

        return back()->with('success', 'Sucursal creada exitosamente.');
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('update', $sucursal);

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('sucursales', 'slug')->ignore($sucursal->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'zona_horaria' => ['required', 'string', 'max:50'],
            'moneda' => ['required', 'string', 'max:10'],
            'idioma' => ['required', 'string', 'in:es,en'],
            'activa' => ['required', 'boolean'],
        ]);

        $sucursal->update($validated);

        return back()->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('delete', $sucursal);

        $this->eliminarConValidacionAction->handle($sucursal, ['users']);

        return back()->with('success', 'Sucursal eliminada lógicamente.');
    }
}
