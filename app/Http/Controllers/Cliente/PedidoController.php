<?php

namespace App\Http\Controllers\Cliente;

use App\Actions\Pedidos\EditarPedidoAction;
use App\Actions\Pedidos\RepetirPedidoAction;
use App\Actions\Pedidos\SolicitarCancelacionAction;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    /**
     * Listado de pedidos del cliente (en curso e historial).
     */
    public function index(Request $request): Response
    {
        $userId = auth()->id();

        $pedidos = Pedido::withoutGlobalScopes()
            ->where('cliente_id', $userId)
            ->with(['sucursal', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'sucursal' => $p->sucursal->nombre,
                'tipo' => $p->tipo->value,
                'tipo_etiqueta' => $p->tipo->etiqueta(),
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'etiqueta_cliente' => $p->etiquetaCliente(),
                'total' => (float) $p->total,
                'items_count' => $p->items->count(),
                'esta_activo' => $p->estaActivo(),
                'puede_editar_notas' => $p->puedeEditarNotas(),
                'solicito_cancelacion' => $p->solicito_cancelacion,
                'created_at' => $p->fechaLocal(),
            ]);

        return Inertia::render('Cliente/Pedidos/Index', [
            'pedidos' => $pedidos,
        ]);
    }

    /**
     * Detalle completo de un pedido del cliente con timeline de estados.
     */
    public function show(Request $request, Pedido $pedido): Response
    {
        $this->authorize('view', $pedido);

        $pedido->loadMissing(['sucursal', 'items.itemMenu', 'estados']);

        return Inertia::render('Cliente/Pedidos/Show', [
            'pedido' => [
                'id' => $pedido->id,
                'numero' => $pedido->numero,
                'sucursal_id' => $pedido->sucursal_id,
                'sucursal_nombre' => $pedido->sucursal->nombre,
                'tipo' => $pedido->tipo->value,
                'tipo_etiqueta' => $pedido->tipo->etiqueta(),
                'estado' => $pedido->estado->value,
                'estado_etiqueta' => $pedido->estado->etiqueta(),
                'etiqueta_cliente' => $pedido->etiquetaCliente(),
                'direccion_entrega' => $pedido->direccion_entrega,
                'subtotal' => (float) $pedido->subtotal,
                'descuento' => (float) $pedido->descuento,
                'impuestos' => (float) $pedido->impuestos,
                'propina' => (float) $pedido->propina,
                'total' => (float) $pedido->total,
                'notas' => $pedido->notas,
                'solicito_cancelacion' => $pedido->solicito_cancelacion,
                'motivo_cancelacion' => $pedido->motivo_cancelacion,
                'esta_activo' => $pedido->estaActivo(),
                'puede_editar_notas' => $pedido->puedeEditarNotas(),
                'fecha' => $pedido->fechaLocal(),
                'items' => $pedido->items->map(fn ($it) => [
                    'id' => $it->id,
                    'item_menu_id' => $it->item_menu_id,
                    'nombre' => $it->nombre_item,
                    'cantidad' => (float) $it->cantidad,
                    'precio_unitario' => (float) $it->precio_unitario,
                    'total' => (float) $it->total,
                    'notas' => $it->notas,
                ]),
                'estados' => $pedido->estados->map(fn ($es) => [
                    'estado' => $es->estado_nuevo,
                    'motivo' => $es->motivo,
                    'hora' => $es->created_at->format('H:i'),
                ]),
            ],
        ]);
    }

    /**
     * Permite al cliente editar las notas de su pedido dentro de la ventana de 5 minutos (CA-10).
     */
    public function editarNotas(Request $request, Pedido $pedido, EditarPedidoAction $editarPedidoAction): RedirectResponse
    {
        $this->authorize('update', $pedido);

        $validated = $request->validate([
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $editarPedidoAction->handle($pedido, $request->user(), [
            'notas' => $validated['notas'] ?? null,
        ]);

        return back()->with('success', 'Notas del pedido actualizadas correctamente.');
    }

    /**
     * Permite al cliente solicitar la cancelación de un pedido activo (CA-08).
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

        return back()->with('success', 'Tu solicitud de cancelación ha sido enviada para revisión.');
    }

    /**
     * Evalúa la disponibilidad de los ítems para repetir un pedido previo (RF-PED-18).
     */
    public function repetir(Pedido $pedido, RepetirPedidoAction $repetirPedidoAction): JsonResponse
    {
        $this->authorize('view', $pedido);

        $resultado = $repetirPedidoAction->handle($pedido);

        return response()->json($resultado);
    }
}
