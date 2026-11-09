<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Compras\AnularCompraAction;
use App\Actions\Compras\DevolverCompraAction;
use App\Actions\Compras\RegistrarCompraAction;
use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\DevolucionCompra;
use App\Models\ItemMenu;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    /**
     * Módulo de compras a proveedores, devoluciones, anulaciones y bitácora (RF-COM-01..08).
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
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', session('sucursal_id', $primerId)));

        session([
            'sucursal_id' => $sucursalId,
            'sucursal_activa_id' => $sucursalId,
        ]);

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a las compras de esta sucursal.');
        }

        $query = $this->construirQueryCompras($request, $sucursalId);

        $compras = $query->orderByDesc('fecha_compra')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Compra $c) => $this->mapearCompraDetalle($c));

        // Pestaña 2: Devoluciones de Compras con acordeón
        $devoluciones = $this->construirQueryDevoluciones($request, $sucursalId)
            ->orderByDesc('fecha_devolucion')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (DevolucionCompra $d): array {
                $compraDev = $d->compra;
                $provDev = $compraDev?->proveedor;
                $userDev = $d->user;

                return [
                    'id' => $d->id,
                    'compra_id' => $d->compra_id,
                    'numero_factura' => $compraDev ? $compraDev->numero_factura : '—',
                    'proveedor' => $provDev ? $provDev->razon_social : '—',
                    'proveedor_nit' => $provDev?->nit,
                    'fecha_devolucion' => $d->fecha_devolucion->format('d/m/Y H:i'),
                    'tipo' => $d->tipo,
                    'motivo' => $d->motivo,
                    'total_devuelto' => (float) $d->total_devuelto,
                    'total_compra_actual' => $compraDev ? (float) $compraDev->total : 0.0,
                    'usuario' => $userDev ? $userDev->nombre : 'Sistema',
                    'items_devueltos' => $d->items_devueltos ?? [],
                ];
            });

        // Pestaña 3: Compras Anuladas con acordeón
        $anulaciones = Compra::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('estado', 'anulada')
            ->when($request->input('fecha_desde'), fn ($q, $d) => $q->whereDate('fecha_compra', '>=', $d))
            ->when($request->input('fecha_hasta'), fn ($q, $h) => $q->whereDate('fecha_compra', '<=', $h))
            ->when($request->input('buscar'), function ($q, $buscar) {
                $term = "%{$buscar}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('numero_factura', 'like', $term)
                        ->orWhere('motivo_anulacion', 'like', $term)
                        ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', $term)->orWhere('nit', 'like', $term));
                });
            })
            ->with(['proveedor', 'user', 'anuladoPor', 'items.itemMenu'])
            ->orderByDesc('anulado_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Compra $c) => $this->mapearCompraDetalle($c));

        // Pestaña 4: Bitácora de Compras, Devoluciones y Anulaciones
        $bitacoraCompras = Activity::query()
            ->with('causer:id,nombre')
            ->where(function ($q) {
                $q->where('log_name', 'compras')
                    ->orWhereIn('subject_type', [
                        Compra::class,
                        DevolucionCompra::class,
                        Proveedor::class,
                    ]);
            })
            ->where(function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId)->orWhereNull('sucursal_id');
            })
            ->when($request->input('fecha_desde'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('fecha_hasta'), fn ($q, $h) => $q->whereDate('created_at', '<=', $h))
            ->when($request->input('buscar'), fn ($q, $b) => $q->where('description', 'like', "%{$b}%"))
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(function (Activity $act): array {
                $causer = $act->causer;

                return [
                    'id' => $act->id,
                    'fecha' => $act->created_at?->format('d/m/Y H:i:s'),
                    'evento' => $act->event ?: $act->log_name,
                    'descripcion' => $act->description,
                    'usuario' => $causer ? (string) $causer->getAttribute('nombre') : 'Sistema',
                    'propiedades' => $act->properties?->toArray() ?? [],
                ];
            });

        $proveedores = Proveedor::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'razon_social', 'nit']);
        $productos = ItemMenu::where('sucursal_id', $sucursalId)->where('activo', true)->get(['id', 'nombre', 'precio']);

        return Inertia::render('Admin/Compras/Index', [
            'compras' => $compras,
            'devoluciones' => $devoluciones,
            'anulaciones' => $anulaciones,
            'bitacoraCompras' => $bitacoraCompras,
            'proveedores' => $proveedores,
            'productos' => $productos,
            'filtros' => $request->only(['sucursal_id', 'estado', 'proveedor_id', 'factura', 'buscar', 'fecha_desde', 'fecha_hasta']),
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Exporta a Excel (CSV con BOM UTF-8) las compras, devoluciones y anulaciones según filtros.
     */
    public function exportarExcel(Request $request): StreamedResponse
    {
        $user = $request->user();
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', session('sucursal_id', $user->sucursalIds()[0] ?? 1)));

        $compras = $this->construirQueryCompras($request, $sucursalId)->orderByDesc('fecha_compra')->get();
        $devoluciones = $this->construirQueryDevoluciones($request, $sucursalId)->orderByDesc('fecha_devolucion')->get();

        $filename = 'informe_compras_devoluciones_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($compras, $devoluciones) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['=== 1. REGISTRO DE COMPRAS, DEVOLUCIONES Y ANULACIONES ==='], ';');
            fputcsv($out, [
                'Factura #',
                'Proveedor',
                'NIT',
                'Fecha Compra',
                'Estado',
                'Subtotal',
                'Impuestos',
                'Total',
                'Registrado Por',
                'Detalle de Productos',
                'Motivo Anulacion / Notas',
            ], ';');

            foreach ($compras as $c) {
                $detalleItems = $c->items->map(fn (CompraItem $it) => "{$it->descripcion} ({$it->cantidad} x {$it->costo_unitario})")->implode(' | ');
                fputcsv($out, [
                    $c->numero_factura,
                    $c->proveedor->razon_social,
                    $c->proveedor->nit,
                    $c->fecha_compra->format('Y-m-d'),
                    $c->estado->etiqueta(),
                    (float) $c->subtotal,
                    (float) $c->impuesto_total,
                    (float) $c->total,
                    $c->user?->nombre,
                    $detalleItems,
                    $c->motivo_anulacion ?: $c->notas,
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['=== 2. DETALLE DE DEVOLUCIONES ASOCIADAS A COMPRAS ==='], ';');
            fputcsv($out, [
                'Factura Compra',
                'Proveedor',
                'Fecha Devolucion',
                'Modalidad',
                'Motivo',
                'Total Devuelto',
                'Usuario',
                'Productos Devueltos',
            ], ';');

            foreach ($devoluciones as $d) {
                $itemsStr = collect($d->items_devueltos ?? [])
                    ->map(fn ($i) => ($i['descripcion'] ?? 'Item').' ('.($i['cantidad_devuelta'] ?? 0).' unid.)')
                    ->implode(' | ');

                fputcsv($out, [
                    $d->compra?->numero_factura,
                    $d->compra?->proveedor?->razon_social,
                    $d->fecha_devolucion->format('Y-m-d H:i'),
                    strtoupper((string) $d->tipo),
                    $d->motivo,
                    (float) $d->total_devuelto,
                    $d->user?->nombre,
                    $itemsStr,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Exporta a PDF las compras, devoluciones y anulaciones según filtros.
     */
    public function exportarPdf(Request $request): HttpResponse
    {
        $user = $request->user();
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', session('sucursal_id', $user->sucursalIds()[0] ?? 1)));
        $sucursal = Sucursal::find($sucursalId);

        $compras = $this->construirQueryCompras($request, $sucursalId)->orderByDesc('fecha_compra')->get();
        $devoluciones = $this->construirQueryDevoluciones($request, $sucursalId)->orderByDesc('fecha_devolucion')->get();
        $anulaciones = $compras->filter(fn (Compra $c) => $c->estado->value === 'anulada')->values();

        $pdf = Pdf::loadView('pdf.compras', [
            'sucursal' => $sucursal,
            'compras' => $compras,
            'devoluciones' => $devoluciones,
            'anulaciones' => $anulaciones,
            'filtros' => $request->only(['buscar', 'fecha_desde', 'fecha_hasta', 'estado']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('informe_compras_devoluciones_'.now()->format('Ymd_His').'.pdf');
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

    /**
     * @return Builder<Compra>
     */
    private function construirQueryCompras(Request $request, int $sucursalId): Builder
    {
        $query = Compra::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['proveedor', 'user', 'items.itemMenu', 'anuladoPor', 'devoluciones.user']);

        if ($filtroEstado = $request->input('estado')) {
            $query->where('estado', $filtroEstado);
        }

        if ($filtroProveedor = $request->input('proveedor_id')) {
            $query->where('proveedor_id', $filtroProveedor);
        }

        if ($filtroFactura = $request->input('factura')) {
            $query->where('numero_factura', 'like', "%{$filtroFactura}%");
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('fecha_compra', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('fecha_compra', '<=', $fechaHasta);
        }

        if ($buscar = trim((string) $request->input('buscar', ''))) {
            $term = "%{$buscar}%";
            $query->where(function ($sub) use ($term) {
                $sub->where('numero_factura', 'like', $term)
                    ->orWhere('notas', 'like', $term)
                    ->orWhere('motivo_anulacion', 'like', $term)
                    ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', $term)->orWhere('nit', 'like', $term))
                    ->orWhereHas('items', fn ($it) => $it->where('descripcion', 'like', $term));
            });
        }

        return $query;
    }

    /**
     * @return Builder<DevolucionCompra>
     */
    private function construirQueryDevoluciones(Request $request, int $sucursalId): Builder
    {
        return DevolucionCompra::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['compra.proveedor', 'user'])
            ->when($request->input('fecha_desde'), fn ($q, $d) => $q->whereDate('fecha_devolucion', '>=', $d))
            ->when($request->input('fecha_hasta'), fn ($q, $h) => $q->whereDate('fecha_devolucion', '<=', $h))
            ->when($request->input('buscar'), function ($q, $buscar) {
                $term = "%{$buscar}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('motivo', 'like', $term)
                        ->orWhereHas('compra', function ($cq) use ($term) {
                            $cq->where('numero_factura', 'like', $term)
                                ->orWhereHas('proveedor', fn ($pq) => $pq->where('razon_social', 'like', $term)->orWhere('nit', 'like', $term));
                        });
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearCompraDetalle(Compra $c): array
    {
        return [
            'id' => $c->id,
            'numero_factura' => $c->numero_factura,
            'proveedor' => $c->proveedor->razon_social ?? '—',
            'proveedor_nit' => $c->proveedor->nit ?? null,
            'proveedor_id' => $c->proveedor_id,
            'fecha_compra' => $c->fecha_compra->format('d/m/Y'),
            'subtotal' => (float) $c->subtotal,
            'impuesto_total' => (float) $c->impuesto_total,
            'total' => (float) $c->total,
            'estado' => $c->estado->value,
            'estado_etiqueta' => $c->estado->etiqueta(),
            'usuario' => $c->user->nombre ?? 'Sistema',
            'items_count' => $c->items->count(),
            'notas' => $c->notas,
            'motivo_anulacion' => $c->motivo_anulacion,
            'anulado_por' => $c->anuladoPor?->nombre,
            'anulado_at' => $c->anulado_at?->format('d/m/Y H:i'),
            'items' => $c->items->map(fn (CompraItem $item) => [
                'id' => $item->id,
                'descripcion' => $item->descripcion,
                'producto_menu' => $item->itemMenu?->nombre,
                'cantidad' => (float) $item->cantidad,
                'factor_conversion' => (float) $item->factor_conversion,
                'costo_unitario' => (float) $item->costo_unitario,
                'total' => (float) $item->total,
            ]),
            'devoluciones' => $c->devoluciones->map(function (DevolucionCompra $dev): array {
                $userDev = $dev->user;

                return [
                    'id' => $dev->id,
                    'fecha' => $dev->fecha_devolucion->format('d/m/Y H:i'),
                    'tipo' => $dev->tipo,
                    'motivo' => $dev->motivo,
                    'total_devuelto' => (float) $dev->total_devuelto,
                    'usuario' => $userDev ? $userDev->nombre : 'Sistema',
                    'items_devueltos' => $dev->items_devueltos ?? [],
                ];
            }),
            'puede_anular' => $c->estado->value === 'registrada',
            'puede_devolver' => in_array($c->estado->value, ['registrada', 'devuelta_parcial'], true),
        ];
    }
}
