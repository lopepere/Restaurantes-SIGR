<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Enums\OrigenUsuario;
use App\Http\Controllers\Controller;
use App\Models\AsignacionBaseCaja;
use App\Models\Caja;
use App\Models\EstacionCocina;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\TelefonoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

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

        $todasSucursales = Sucursal::orderBy('nombre')->get(['id', 'nombre', 'activa']);

        // Determinar la sede activa actual del SuperAdmin desde la sesión del menú izquierdo
        $rawActiva = session('sucursal_activa_id', session('sucursal_id', $request->input('sucursal_id')));
        $primeraSucursal = $todasSucursales->first();
        $primeraId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalActivaId = (is_numeric($rawActiva) && (int) $rawActiva > 0) ? (int) $rawActiva : $primeraId;

        session([
            'sucursal_id' => $sucursalActivaId,
            'sucursal_activa_id' => $sucursalActivaId,
        ]);

        $sucursalActiva = Sucursal::find($sucursalActivaId) ?? $todasSucursales->first();

        // Usuarios cajeros y de cocina enlazados a la sede activa
        $usuariosCajeros = User::role('cajero')
            ->whereHas('sucursales', fn ($q) => $q->where('sucursales.id', $sucursalActivaId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email', 'telefono', 'activo']);

        $usuariosCocina = User::role('cocinero')
            ->whereHas('sucursales', fn ($q) => $q->where('sucursales.id', $sucursalActivaId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email', 'telefono', 'activo']);

        // Cajas de la sede activa
        $cajasSede = Caja::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalActivaId)
            ->with('cajero:id,nombre,email')
            ->orderBy('id')
            ->get();

        // Estaciones / Monitores de cocina de la sede activa
        $estacionesCocina = Schema::hasTable('estaciones_cocina')
            ? EstacionCocina::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalActivaId)
                ->with('cocinero:id,nombre,email')
                ->orderBy('id')
                ->get()
            : collect();

        // Registros diarios de asignación de base en efectivo de la sede activa
        $asignacionesBase = Schema::hasTable('asignaciones_base_caja')
            ? AsignacionBaseCaja::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalActivaId)
                ->with([
                    'caja:id,nombre',
                    'cajero:id,nombre,email',
                    'asignadoPor:id,nombre',
                    'editadoPor:id,nombre',
                ])
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit(40)
                ->get()
                ->map(fn (AsignacionBaseCaja $a) => [
                    'id' => $a->id,
                    'caja_id' => $a->caja_id,
                    'caja_nombre' => $a->caja->nombre ?? 'Caja eliminada',
                    'cajero_nombre' => $a->cajero->nombre ?? 'Sin cajero fijo',
                    'fecha' => $a->fecha->format('Y-m-d'),
                    'fecha_formateada' => $a->fecha->format('d/m/Y'),
                    'monto' => (float) $a->monto,
                    'observaciones' => $a->observaciones,
                    'asignado_por' => $a->asignadoPor->nombre ?? 'Sistema',
                    'editado_por' => $a->editadoPor?->nombre,
                    'updated_at' => $a->updated_at?->format('d/m/Y H:i'),
                ])
            : collect();

        // Bitácora rápida de movimientos de cajas, bases y cocina en la sede activa
        $bitacoraSede = Activity::query()
            ->with('causer:id,nombre')
            ->where(function ($q) use ($sucursalActivaId) {
                $q->where('sucursal_id', $sucursalActivaId)
                    ->orWhereIn('subject_type', [
                        Caja::class,
                        EstacionCocina::class,
                        AsignacionBaseCaja::class,
                        Sucursal::class,
                    ]);
            })
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(function (Activity $act): array {
                $causer = $act->causer;

                return [
                    'id' => $act->id,
                    'fecha' => $act->created_at?->format('d/m/Y H:i'),
                    'modulo' => $act->log_name,
                    'descripcion' => $act->description,
                    'usuario' => $causer ? (string) $causer->getAttribute('nombre') : 'Sistema',
                ];
            });

        return Inertia::render('SuperAdmin/Sucursales/Index', [
            'sucursales' => $sucursales,
            'todasSucursales' => $todasSucursales,
            'sucursalActiva' => $sucursalActiva,
            'cajasSede' => $cajasSede,
            'estacionesCocina' => $estacionesCocina,
            'usuariosCajeros' => $usuariosCajeros,
            'usuariosCocina' => $usuariosCocina,
            'asignacionesBase' => $asignacionesBase,
            'bitacoraSede' => $bitacoraSede,
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
            'acepta_domicilios' => ['nullable', 'boolean'],
            'acepta_para_llevar' => ['nullable', 'boolean'],
            'acepta_en_sitio' => ['nullable', 'boolean'],
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
            'acepta_domicilios' => $validated['acepta_domicilios'] ?? true,
            'acepta_para_llevar' => $validated['acepta_para_llevar'] ?? true,
            'acepta_en_sitio' => $validated['acepta_en_sitio'] ?? true,
            'activa' => true,
        ]);

        return back()->with('success', 'Sucursal creada exitosamente.');
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('update', $sucursal);

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        if (! $request->filled('slug')) {
            $request->merge(['slug' => $sucursal->slug ?: Str::slug((string) $request->input('nombre'))]);
        }
        if (! $request->filled('zona_horaria')) {
            $request->merge(['zona_horaria' => $sucursal->zona_horaria ?? 'America/Bogota']);
        }
        if (! $request->filled('moneda')) {
            $request->merge(['moneda' => $sucursal->moneda ?? 'COP']);
        }
        if (! $request->filled('idioma')) {
            $request->merge(['idioma' => $sucursal->idioma ?? 'es']);
        }
        if (! $request->has('activa')) {
            $request->merge(['activa' => $sucursal->activa ?? true]);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('sucursales', 'slug')->ignore($sucursal->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'zona_horaria' => ['required', 'string', 'max:50'],
            'moneda' => ['required', 'string', 'max:10'],
            'idioma' => ['required', 'string', 'in:es,en'],
            'activa' => ['required', 'boolean'],
            'acepta_domicilios' => ['nullable', 'boolean'],
            'acepta_para_llevar' => ['nullable', 'boolean'],
            'acepta_en_sitio' => ['nullable', 'boolean'],
        ]);

        $sucursal->update($validated);

        return back()->with('success', 'Sucursal actualizada exitosamente.');
    }

    /**
     * Activa o desactiva una sucursal existente (exclusivo SuperAdmin).
     */
    public function toggleEstado(Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('update', $sucursal);

        $sucursal->update(['activa' => ! $sucursal->activa]);
        $estadoTexto = $sucursal->activa ? 'activada' : 'desactivada';

        return back()->with('success', "Sucursal '{$sucursal->nombre}' {$estadoTexto} correctamente.");
    }

    /**
     * Alterna la habilitación de una modalidad de pedido (domicilio, para llevar, en sitio) (Item 9).
     */
    public function toggleModalidad(Request $request, Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('update', $sucursal);
        $modalidad = (string) $request->input('modalidad');

        if (in_array($modalidad, ['domicilio', 'para_llevar', 'en_sitio'], true)) {
            $campo = match ($modalidad) {
                'domicilio' => 'acepta_domicilios',
                'para_llevar' => 'acepta_para_llevar',
                'en_sitio' => 'acepta_en_sitio',
            };
            $sucursal->update([$campo => ! $sucursal->{$campo}]);
        }

        return back()->with('success', 'Modalidad de pedido actualizada para la sucursal.');
    }

    public function destroy(Sucursal $sucursal): RedirectResponse
    {
        Gate::authorize('delete', $sucursal);

        $this->eliminarConValidacionAction->handle($sucursal, ['users']);

        return back()->with('success', 'Sucursal eliminada lógicamente.');
    }

    /**
     * Crea una caja enlazada a la sede activa seleccionada.
     */
    public function storeCaja(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $caja = Caja::withoutGlobalScopes()->create([
            'sucursal_id' => (int) $validated['sucursal_id'],
            'nombre' => $validated['nombre'],
            'user_id' => $validated['user_id'] ?? null,
            'activa' => $validated['activa'] ?? true,
        ]);

        activity('caja')
            ->performedOn($caja)
            ->causedBy($request->user())
            ->withProperties(['sucursal_id' => $caja->sucursal_id, 'user_id' => $caja->user_id])
            ->log("Caja '{$caja->nombre}' creada en la sede #{$caja->sucursal_id}.");

        return back()->with('success', "Caja '{$caja->nombre}' creada en la sede activa.");
    }

    /**
     * Actualiza una caja (nombre, usuario cajero asignado, o estado activo/inactivo).
     */
    public function updateCaja(Request $request, Caja $caja): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
            'activa' => ['required', 'boolean'],
        ]);

        $caja->update($validated);

        activity('caja')
            ->performedOn($caja)
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log("Caja '{$caja->nombre}' actualizada (Activa: ".($caja->activa ? 'Sí' : 'No').').');

        return back()->with('success', "Caja '{$caja->nombre}' actualizada correctamente.");
    }

    /**
     * Crea un monitor/vista de cocina enlazado a la sede activa.
     */
    public function storeEstacionCocina(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $estacion = EstacionCocina::withoutGlobalScopes()->create([
            'sucursal_id' => (int) $validated['sucursal_id'],
            'nombre' => $validated['nombre'],
            'codigo' => $validated['codigo'] ?: Str::upper(Str::slug($validated['nombre'])),
            'user_id' => $validated['user_id'] ?? null,
            'activa' => $validated['activa'] ?? true,
        ]);

        activity('cocina')
            ->performedOn($estacion)
            ->causedBy($request->user())
            ->withProperties(['sucursal_id' => $estacion->sucursal_id, 'user_id' => $estacion->user_id])
            ->log("Monitor de cocina '{$estacion->nombre}' creado en la sede #{$estacion->sucursal_id}.");

        return back()->with('success', "Monitor de cocina '{$estacion->nombre}' registrado en la sede activa.");
    }

    /**
     * Actualiza un monitor/vista de cocina (nombre, usuario de cocina asignado, o estado activo/inactivo).
     */
    public function updateEstacionCocina(Request $request, EstacionCocina $estacion): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'activa' => ['required', 'boolean'],
        ]);

        $estacion->update($validated);

        activity('cocina')
            ->performedOn($estacion)
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log("Monitor de cocina '{$estacion->nombre}' actualizado (Activo: ".($estacion->activa ? 'Sí' : 'No').').');

        return back()->with('success', "Monitor de cocina '{$estacion->nombre}' actualizado correctamente.");
    }

    /**
     * Crea un usuario de Caja (`cajero`) o Cocina (`cocinero`) con contraseña y enlazado a la sede activa.
     */
    public function storeUsuarioOperativo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'rol' => ['required', 'string', 'in:cajero,cocinero'],
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'caja_id' => ['nullable', 'exists:cajas,id'],
            'estacion_cocina_id' => ['nullable', 'exists:estaciones_cocina,id'],
        ]);

        $sucursalId = (int) $validated['sucursal_id'];

        $usuario = User::create([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => ! empty($validated['telefono']) ? TelefonoService::normalizar($validated['telefono']) : null,
            'password' => $validated['password'],
            'idioma' => 'es',
            'activo' => true,
            'origen' => OrigenUsuario::PERSONAL,
            'email_verified_at' => now(),
        ]);

        $usuario->assignRole($validated['rol']);
        $usuario->sucursales()->syncWithoutDetaching([$sucursalId]);

        if ($validated['rol'] === 'cajero' && ! empty($validated['caja_id'])) {
            Caja::withoutGlobalScopes()
                ->where('id', $validated['caja_id'])
                ->where('sucursal_id', $sucursalId)
                ->update(['user_id' => $usuario->id]);
        }

        if ($validated['rol'] === 'cocinero' && ! empty($validated['estacion_cocina_id'])) {
            EstacionCocina::withoutGlobalScopes()
                ->where('id', $validated['estacion_cocina_id'])
                ->where('sucursal_id', $sucursalId)
                ->update(['user_id' => $usuario->id]);
        }

        activity('usuarios')
            ->performedOn($usuario)
            ->causedBy($request->user())
            ->withProperties(['rol' => $validated['rol'], 'sucursal_id' => $sucursalId])
            ->log("Usuario de {$validated['rol']} '{$usuario->nombre}' creado y enlazado a la sede #{$sucursalId}.");

        return back()->with('success', "Usuario '{$usuario->nombre}' ({$validated['rol']}) creado y enlazado a la sede activa.");
    }

    /**
     * Actualiza datos, contraseña o estado activo de un usuario cajero o cocinero.
     */
    public function updateUsuarioOperativo(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'activo' => ['required', 'boolean'],
        ]);

        $datos = [
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => ! empty($validated['telefono']) ? TelefonoService::normalizar($validated['telefono']) : null,
            'activo' => $validated['activo'],
        ];

        if (! empty($validated['password'])) {
            $datos['password'] = $validated['password'];
        }

        $usuario->update($datos);

        activity('usuarios')
            ->performedOn($usuario)
            ->causedBy($request->user())
            ->withProperties(['activo' => $usuario->activo, 'cambio_password' => ! empty($validated['password'])])
            ->log("Usuario operativo '{$usuario->nombre}' actualizado.");

        return back()->with('success', "Usuario '{$usuario->nombre}' actualizado correctamente.");
    }

    /**
     * Asigna un valor de base en efectivo para una caja en una fecha específica y lo guarda en bitácora.
     */
    public function storeBaseCaja(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'caja_id' => ['required', 'exists:cajas,id'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $caja = Caja::withoutGlobalScopes()->findOrFail($validated['caja_id']);
        $sucursalId = (int) $caja->sucursal_id;

        $asignacion = AsignacionBaseCaja::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('caja_id', $caja->id)
            ->whereDate('fecha', $validated['fecha'])
            ->first();

        if ($asignacion) {
            $montoAnterior = (float) $asignacion->monto;
            $asignacion->update([
                'monto' => (float) $validated['monto'],
                'user_id' => $caja->user_id,
                'editado_por_id' => $request->user()?->id,
                'observaciones' => $validated['observaciones'] ?? $asignacion->observaciones,
            ]);

            activity('caja')
                ->performedOn($asignacion)
                ->causedBy($request->user())
                ->withProperties([
                    'sucursal_id' => $sucursalId,
                    'caja_id' => $caja->id,
                    'caja_nombre' => $caja->nombre,
                    'fecha' => $validated['fecha'],
                    'monto_anterior' => $montoAnterior,
                    'monto_nuevo' => (float) $validated['monto'],
                    'editado_por' => $request->user()?->nombre,
                ])
                ->log("Base de efectivo editada para '{$caja->nombre}' ({$validated['fecha']}): de $".number_format($montoAnterior, 0).' a $'.number_format((float) $validated['monto'], 0));
        } else {
            $asignacion = AsignacionBaseCaja::withoutGlobalScopes()->create([
                'sucursal_id' => $sucursalId,
                'caja_id' => $caja->id,
                'user_id' => $caja->user_id,
                'asignado_por_id' => $request->user()?->id,
                'fecha' => $validated['fecha'],
                'monto' => (float) $validated['monto'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            activity('caja')
                ->performedOn($asignacion)
                ->causedBy($request->user())
                ->withProperties([
                    'sucursal_id' => $sucursalId,
                    'caja_id' => $caja->id,
                    'caja_nombre' => $caja->nombre,
                    'fecha' => $validated['fecha'],
                    'monto' => (float) $validated['monto'],
                    'asignado_por' => $request->user()?->nombre,
                ])
                ->log("Base de efectivo asignada para '{$caja->nombre}' ({$validated['fecha']}): $".number_format((float) $validated['monto'], 0));
        }

        // Sincronizar base predeterminada de la sucursal si la asignación es del día de hoy
        ConfiguracionService::set('caja.base_efectivo_inicial', (float) $validated['monto'], $sucursalId);

        return back()->with('success', 'Base en efectivo de $'.number_format((float) $validated['monto'], 0)." registrada para '{$caja->nombre}'.");
    }

    /**
     * Edita el monto de una asignación de base en efectivo existente, registrando quién lo editó y dejando traza en bitácora.
     */
    public function updateBaseCaja(Request $request, AsignacionBaseCaja $asignacion): RedirectResponse
    {
        $validated = $request->validate([
            'monto' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $montoAnterior = (float) $asignacion->monto;
        $caja = $asignacion->caja;

        $asignacion->update([
            'monto' => (float) $validated['monto'],
            'editado_por_id' => $request->user()?->id,
            'observaciones' => $validated['observaciones'] ?? $asignacion->observaciones,
        ]);

        ConfiguracionService::set('caja.base_efectivo_inicial', (float) $validated['monto'], $asignacion->sucursal_id);

        activity('caja')
            ->performedOn($asignacion)
            ->causedBy($request->user())
            ->withProperties([
                'sucursal_id' => $asignacion->sucursal_id,
                'caja_id' => $asignacion->caja_id,
                'caja_nombre' => $caja?->nombre,
                'fecha' => $asignacion->fecha->format('Y-m-d'),
                'monto_anterior' => $montoAnterior,
                'monto_nuevo' => (float) $validated['monto'],
                'editado_por' => $request->user()?->nombre,
            ])
            ->log("Monto de base de caja editado por {$request->user()?->nombre} en '{$caja?->nombre}' ({$asignacion->fecha->format('d/m/Y')}): de $".number_format($montoAnterior, 0).' a $'.number_format((float) $validated['monto'], 0));

        return back()->with('success', 'Monto de base en efectivo actualizado y registrado en bitácora.');
    }
}
