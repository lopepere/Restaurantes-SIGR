<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModoInventario;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ItemMenu;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    /**
     * Tablero de control de inventario, kárdex y alertas de existencias (RF-INV-01..09, CA-27, CA-28).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $esSuperAdmin = $user->hasRole('super_admin');

        $sucursales = $esSuperAdmin
            ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre'])
            : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre']);

        $primeraSucursal = $sucursales->first();
        $primerId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', $primerId));

        $query = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->where('controla_inventario', true)
            ->with(['categoria']);

        if ($filtroCategoria = $request->input('categoria_id')) {
            $query->where('categoria_id', $filtroCategoria);
        }

        if ($request->boolean('solo_bajo_stock')) {
            $query->whereColumn('existencia', '<=', 'existencia_minima');
        }

        if ($busqueda = $request->input('q')) {
            $query->where('nombre', 'like', "%{$busqueda}%");
        }

        $items = $query->orderBy('nombre')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ItemMenu $item) => [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'categoria' => $item->categoria->nombre,
                'categoria_id' => $item->categoria_id,
                'precio' => (float) $item->precio,
                'existencia' => (float) $item->existencia,
                'existencia_minima' => (float) $item->existencia_minima,
                'bajo_stock' => (float) $item->existencia <= (float) $item->existencia_minima,
                'disponible' => $item->disponible,
            ]);

        // Movimientos recientes del kárdex
        $ultimosMovimientos = MovimientoInventario::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['itemMenu', 'user'])
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(fn (MovimientoInventario $m) => [
                'id' => $m->id,
                'fecha' => $m->created_at->format('d/m/Y H:i'),
                'producto' => $m->itemMenu ? $m->itemMenu->nombre : 'Producto general',
                'tipo' => $m->tipo->value,
                'tipo_etiqueta' => $m->tipo->etiqueta(),
                'es_ingreso' => $m->tipo->esIngreso(),
                'cantidad' => (float) $m->cantidad,
                'saldo_cantidad' => (float) $m->saldo_cantidad,
                'motivo' => $m->motivo,
                'usuario' => $m->user?->nombre,
            ]);

        $categorias = Categoria::where('sucursal_id', $sucursalId)->where('activa', true)->get(['id', 'nombre']);
        $modoInventario = InventarioService::obtenerModo($sucursalId);
        $alertasCount = InventarioService::alertasStockMinimo($sucursalId)->count();

        return Inertia::render('Admin/Inventario/Index', [
            'items' => $items,
            'ultimosMovimientos' => $ultimosMovimientos,
            'categorias' => $categorias,
            'modoInventario' => $modoInventario->value,
            'modoInventarioEtiqueta' => $modoInventario->etiqueta(),
            'alertasCount' => $alertasCount,
            'filtros' => $request->only(['sucursal_id', 'categoria_id', 'solo_bajo_stock', 'q']),
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
            'esSuperAdmin' => $esSuperAdmin,
        ]);
    }

    /**
     * Ajuste manual individual de stock con motivo (RF-INV-05).
     */
    public function ajustar(Request $request, ItemMenu $itemMenu): RedirectResponse
    {
        $validated = $request->validate([
            'nueva_existencia' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        InventarioService::ajustar(
            $itemMenu,
            (float) $validated['nueva_existencia'],
            $validated['motivo'],
            $request->user()
        );

        return back()->with('success', "Existencia de '{$itemMenu->nombre}' ajustada a {$validated['nueva_existencia']}.");
    }

    /**
     * Ajuste masivo por lote (RF-INV-06).
     */
    public function ajustarLote(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'motivo' => ['required', 'string', 'min:3', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items_menu,id'],
            'items.*.nueva_existencia' => ['required', 'numeric', 'min:0'],
        ]);

        InventarioService::actualizarPorLote(
            (int) $validated['sucursal_id'],
            $validated['items'],
            $validated['motivo'],
            $request->user()
        );

        return back()->with('success', 'Ajuste de inventario por lote procesado exitosamente.');
    }

    /**
     * Cambia el modo de inventario (restringido a super_admin).
     */
    public function cambiarModo(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            abort(403, 'Solo el Super Administrador puede cambiar el modo de inventario de la sucursal.');
        }

        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'modo' => ['required', 'string', 'in:autonomo,dependiente'],
        ]);

        $modo = ModoInventario::from($validated['modo']);
        InventarioService::establecerModo((int) $validated['sucursal_id'], $modo);

        return back()->with('success', "Modo de inventario cambiado a {$modo->etiqueta()}.");
    }
}
