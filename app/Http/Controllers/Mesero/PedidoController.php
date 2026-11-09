<?php

namespace App\Http\Controllers\Mesero;

use App\Actions\Pedidos\CrearPedidoAction;
use App\Actions\Pedidos\EditarPedidoAction;
use App\Actions\Pedidos\SolicitarCancelacionAction;
use App\Enums\TipoPedido;
use App\Http\Controllers\Controller;
use App\Models\Espacio;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    /**
     * Comandas y pedidos de la sucursal activa para el mesero.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sucursalId = (int) ($sessionSucursal = session('sucursal_activa_id', $user->sucursalIds()[0] ?? 1));

        $pedidos = Pedido::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['cliente', 'mesero', 'espacio', 'items'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'es_propio' => (int) $p->mesero_id === (int) $user->id,
                'tipo' => $p->tipo->value,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'mesa' => $p->espacio?->nombre,
                'cliente' => $p->cliente ? $p->cliente->nombre : ($p->nombre_contacto ?? 'Consumidor final'),
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
                'puede_editar' => (int) $p->mesero_id === (int) $user->id && $p->puedeEditarItems(),
                'solicito_cancelacion' => $p->solicito_cancelacion,
                'created_at' => $p->fechaLocal(),
            ]);

        return Inertia::render('Mesero/Pedidos/Index', [
            'pedidos' => $pedidos,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Formulario de toma de comanda en mesa o para llevar.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $sucursalId = (int) session('sucursal_activa_id', $user->sucursalIds()[0] ?? 1);

        $espacios = Espacio::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo', 'capacidad', 'estado']);

        $items = ItemMenu::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->where('disponible', true)
            ->with(['categoria:id,nombre'])
            ->orderBy('nombre')
            ->get(['id', 'categoria_id', 'nombre', 'precio', 'es_combo', 'existencia', 'controla_inventario']);

        return Inertia::render('Mesero/Pedidos/Create', [
            'sucursal_id' => $sucursalId,
            'espacios' => $espacios,
            'items' => $items,
        ]);
    }

    /**
     * Guarda una comanda tomada por el mesero.
     */
    public function store(Request $request, CrearPedidoAction $crearPedidoAction): RedirectResponse
    {
        $user = $request->user();
        $sucursalId = (int) session('sucursal_activa_id', $user->sucursalIds()[0] ?? 1);

        $validated = $request->validate([
            'tipo' => ['required', 'string'],
            'espacio_id' => ['nullable', 'exists:espacios,id'],
            'nombre_contacto' => ['nullable', 'string', 'max:100'],
            'telefono_contacto' => ['nullable', 'string', 'max:30'],
            'notas' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_menu_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:1'],
            'items.*.notas' => ['nullable', 'string', 'max:255'],
        ]);

        $tipo = TipoPedido::tryFrom($validated['tipo']) ?? TipoPedido::EN_SITIO;

        $pedido = $crearPedidoAction->handle([
            'sucursal_id' => $sucursalId,
            'tipo' => $tipo,
            'mesero_id' => $user->id,
            'espacio_id' => $validated['espacio_id'] ?? null,
            'nombre_contacto' => $validated['nombre_contacto'] ?? null,
            'telefono_contacto' => $validated['telefono_contacto'] ?? null,
            'notas' => $validated['notas'] ?? null,
            'items' => $validated['items'],
        ]);

        return redirect()->route('mesero.pedidos.index')
            ->with('success', "Comanda {$pedido->numero} creada exitosamente.");
    }

    /**
     * Edita los ítems o notas de una comanda dentro de la ventana de 5 minutos (CA-10).
     */
    public function editar(Request $request, Pedido $pedido, EditarPedidoAction $editarPedidoAction): RedirectResponse
    {
        $this->authorize('update', $pedido);

        $validated = $request->validate([
            'notas' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.item_menu_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:1'],
            'items.*.notas' => ['nullable', 'string', 'max:255'],
        ]);

        $editarPedidoAction->handle($pedido, $request->user(), $validated);

        return back()->with('success', "Comanda {$pedido->numero} modificada correctamente.");
    }

    /**
     * Solicita la cancelación de una comanda (CA-08).
     */
    public function solicitarCancelacion(
        Request $request,
        Pedido $pedido,
        SolicitarCancelacionAction $solicitarCancelacionAction
    ): RedirectResponse {
        $this->authorize('cancel', $pedido);

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $solicitarCancelacionAction->handle(
            $pedido,
            $validated['motivo'],
            $request->user()->id,
            $request->user()->name,
            $request->user()->telefono
        );

        return back()->with('success', 'Solicitud de cancelación remitida a administración.');
    }
}
