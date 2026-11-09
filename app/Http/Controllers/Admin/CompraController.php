<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Compras\AnularCompraAction;
use App\Actions\Compras\DevolverCompraAction;
use App\Actions\Compras\RegistrarCompraAction;
use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\ItemMenu;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompraController extends Controller
{
    /**
     * Módulo de compras a proveedores (RF-COM-01..08).
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

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a las compras de esta sucursal.');
        }

        $query = Compra::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['proveedor', 'user', 'items.itemMenu', 'anuladoPor', 'devoluciones']);

        if ($filtroEstado = $request->input('estado')) {
            $query->where('estado', $filtroEstado);
        }

        if ($filtroProveedor = $request->input('proveedor_id')) {
            $query->where('proveedor_id', $filtroProveedor);
        }

        if ($filtroFactura = $request->input('factura')) {
            $query->where('numero_factura', 'like', "%{$filtroFactura}%");
        }

        $compras = $query->orderByDesc('fecha_compra')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Compra $c) => [
                'id' => $c->id,
                'numero_factura' => $c->numero_factura,
                'proveedor' => $c->proveedor->razon_social,
                'proveedor_id' => $c->proveedor_id,
                'fecha_compra' => $c->fecha_compra->format('d/m/Y'),
                'subtotal' => (float) $c->subtotal,
                'impuesto_total' => (float) $c->impuesto_total,
                'total' => (float) $c->total,
                'estado' => $c->estado->value,
                'estado_etiqueta' => $c->estado->etiqueta(),
                'usuario' => $c->user->nombre,
                'items_count' => $c->items->count(),
                'notas' => $c->notas,
                'motivo_anulacion' => $c->motivo_anulacion,
                'items' => $c->items->map(fn (CompraItem $item) => [
                    'id' => $item->id,
                    'descripcion' => $item->descripcion,
                    'cantidad' => (float) $item->cantidad,
                    'costo_unitario' => (float) $item->costo_unitario,
                    'total' => (float) $item->total,
                ]),
                'puede_anular' => $c->estado->value === 'registrada',
                'puede_devolver' => in_array($c->estado->value, ['registrada', 'devuelta_parcial'], true),
            ]);

        $proveedores = Proveedor::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'razon_social', 'nit']);
        $productos = ItemMenu::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'nombre', 'precio']);

        return Inertia::render('Admin/Compras/Index', [
            'compras' => $compras,
            'proveedores' => $proveedores,
            'productos' => $productos,
            'filtros' => $request->only(['sucursal_id', 'estado', 'proveedor_id', 'factura']),
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Registra una nueva compra a proveedor (RF-COM-02).
     */
    public function store(Request $request, RegistrarCompraAction $registrarAction): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'numero_factura' => ['required', 'string', 'max:50'],
            'fecha_compra' => ['required', 'date'],
            'incluye_impuesto' => ['nullable', 'boolean'],
            'impuesto_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notas' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_menu_id' => ['nullable', 'exists:items_menu,id'],
            'items.*.descripcion' => ['required', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items.*.costo_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.factor_conversion' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $compra = $registrarAction->handle($validated, $request->user());

        return back()->with('success', "Compra con Factura #{$compra->numero_factura} registrada exitosamente.");
    }

    /**
     * Anula una compra registrada (RF-COM-06).
     */
    public function anular(Request $request, Compra $compra, AnularCompraAction $anularAction): RedirectResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:300'],
        ]);

        $anularAction->handle($compra, $validated['motivo'], $request->user());

        return back()->with('success', "Compra Factura #{$compra->numero_factura} anulada.");
    }

    /**
     * Registra la devolución formal (total o parcial) de una compra (RF-COM-07, Item 4).
     */
    public function devolver(Request $request, Compra $compra, DevolverCompraAction $devolverAction): RedirectResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:300'],
            'tipo' => ['nullable', 'string', 'in:total,parcial'],
            'items' => ['nullable', 'array'],
            'items.*.compra_item_id' => ['required_with:items', 'integer'],
            'items.*.cantidad' => ['required_with:items', 'numeric', 'min:0.01'],
        ]);

        $devolverAction->handle(
            $compra,
            $validated['motivo'],
            ($validated['tipo'] ?? 'total') === 'parcial' ? ($validated['items'] ?? null) : null,
            $request->user()
        );

        return back()->with('success', "Devolución de Compra #{$compra->numero_factura} registrada correctamente.");
    }

    /**
     * Registra un nuevo proveedor de insumos (RF-COM-01).
     */
    public function storeProveedor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:30'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'contacto_nombre' => ['nullable', 'string', 'max:100'],
        ]);

        Proveedor::create($validated);

        return back()->with('success', 'Proveedor registrado correctamente.');
    }
}
