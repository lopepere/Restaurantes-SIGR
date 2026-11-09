<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Enums\TipoBeneficioPromocion;
use App\Events\MenuActualizado;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ItemMenu;
use App\Models\Promocion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class PromocionController extends Controller
{
    public function __construct(
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Promocion::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $promociones = Promocion::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['items:id,nombre', 'categorias:id,nombre'])
            ->latest()
            ->paginate(25);

        $categorias = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activa', true)
            ->get(['id', 'nombre']);

        $items = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->get(['id', 'nombre']);

        return Inertia::render('Admin/Promociones/Index', [
            'promociones' => $promociones,
            'categorias' => $categorias,
            'items' => $items,
            'tiposBeneficio' => array_column(TipoBeneficioPromocion::cases(), 'value'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Promocion::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo_beneficio' => ['required', new Enum(TipoBeneficioPromocion::class)],
            'valor_beneficio' => ['required', 'numeric', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'aplica_a_todo' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*' => ['integer', 'exists:items_menu,id'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ]);

        $promocion = Promocion::create([
            'sucursal_id' => $sucursalId,
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo_beneficio' => $validated['tipo_beneficio'],
            'valor_beneficio' => $validated['valor_beneficio'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'dias_semana' => $validated['dias_semana'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'activa' => true,
            'publicada' => false,
            'aplica_a_todo' => $validated['aplica_a_todo'] ?? false,
        ]);

        if (! empty($validated['items'])) {
            $promocion->items()->sync($validated['items']);
        }

        if (! empty($validated['categorias'])) {
            $promocion->categorias()->sync($validated['categorias']);
        }

        // Evaluar publicación inmediata si ya está vigente
        if ($promocion->estaVigente()) {
            $promocion->update(['publicada' => true]);
        }

        event(new MenuActualizado($sucursalId, 'promocion', "Promoción {$promocion->nombre} creada."));

        return back()->with('success', 'Promoción registrada exitosamente.');
    }

    public function update(Request $request, Promocion $promocion): RedirectResponse
    {
        Gate::authorize('update', $promocion);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo_beneficio' => ['required', new Enum(TipoBeneficioPromocion::class)],
            'valor_beneficio' => ['required', 'numeric', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'activa' => ['required', 'boolean'],
            'aplica_a_todo' => ['required', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*' => ['integer', 'exists:items_menu,id'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ]);

        $promocion->update([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo_beneficio' => $validated['tipo_beneficio'],
            'valor_beneficio' => $validated['valor_beneficio'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'dias_semana' => $validated['dias_semana'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'activa' => $validated['activa'],
            'aplica_a_todo' => $validated['aplica_a_todo'],
        ]);

        $promocion->items()->sync($validated['items'] ?? []);
        $promocion->categorias()->sync($validated['categorias'] ?? []);

        // Reevaluar publicación
        $promocion->update(['publicada' => $promocion->estaVigente()]);

        event(new MenuActualizado($promocion->sucursal_id, 'promocion', "Promoción {$promocion->nombre} actualizada."));

        return back()->with('success', 'Promoción actualizada.');
    }

    public function destroy(Promocion $promocion): RedirectResponse
    {
        Gate::authorize('delete', $promocion);

        $this->eliminarConValidacionAction->handle($promocion);

        event(new MenuActualizado($promocion->sucursal_id, 'promocion', "Promoción {$promocion->nombre} eliminada."));

        return back()->with('success', 'Promoción enviada a la papelera.');
    }
}
