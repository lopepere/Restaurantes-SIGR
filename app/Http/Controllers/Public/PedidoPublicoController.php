<?php

namespace App\Http\Controllers\Public;

use App\Actions\Pedidos\CrearPedidoAction;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoPublicoController extends Controller
{
    /**
     * Procesa la creación de un pedido desde el sitio público (cliente registrado o invitado).
     */
    public function store(Request $request, CrearPedidoAction $crearPedidoAction): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'tipo' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_menu_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:1'],
            'items.*.notas' => ['nullable', 'string', 'max:255'],
            'nombre_contacto' => ['nullable', 'string', 'max:100'],
            'email_contacto' => ['nullable', 'email', 'max:255'],
            'telefono_contacto' => ['nullable', 'string', 'max:30'],
            'direccion_entrega' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:500'],
            'propina_tipo' => ['nullable', 'string'],
            'propina_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'propina_valor' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tipo = TipoPedido::tryFrom($validated['tipo']) ?? TipoPedido::EN_SITIO;
        $propinaTipo = isset($validated['propina_tipo'])
            ? (PropinaTipo::tryFrom($validated['propina_tipo']) ?? PropinaTipo::NINGUNA)
            : PropinaTipo::NINGUNA;

        $clienteId = auth()->check() && auth()->user()->hasRole('cliente')
            ? auth()->id()
            : null;

        $pedido = $crearPedidoAction->handle([
            'sucursal_id' => (int) $validated['sucursal_id'],
            'tipo' => $tipo,
            'items' => $validated['items'],
            'cliente_id' => $clienteId,
            'nombre_contacto' => $validated['nombre_contacto'] ?? (auth()->user()?->name),
            'email_contacto' => $validated['email_contacto'] ?? (auth()->user()?->email),
            'telefono_contacto' => $validated['telefono_contacto'] ?? (auth()->user()?->telefono),
            'direccion_entrega' => $validated['direccion_entrega'] ?? (auth()->user()?->direccion),
            'notas' => $validated['notas'] ?? null,
            'propina_tipo' => $propinaTipo,
            'propina_porcentaje' => $validated['propina_porcentaje'] ?? null,
            'propina_valor' => $validated['propina_valor'] ?? null,
        ]);

        return redirect()->route('pedidos.publico.confirmado', ['numero' => $pedido->numero])
            ->with('success', '¡Tu pedido ha sido recibido con éxito!');
    }

    /**
     * Muestra la pantalla de confirmación y seguimiento en vivo del pedido público.
     */
    public function confirmado(Request $request, string $numero): Response
    {
        /** @var Pedido $pedido */
        $pedido = Pedido::withoutGlobalScopes()
            ->where('numero', $numero)
            ->with(['sucursal', 'items.itemMenu', 'estados'])
            ->firstOrFail();

        return Inertia::render('Public/PedidoConfirmado', [
            'pedido' => [
                'id' => $pedido->id,
                'numero' => $pedido->numero,
                'sucursal' => [
                    'id' => $pedido->sucursal->id,
                    'nombre' => $pedido->sucursal->nombre,
                    'slug' => $pedido->sucursal->slug,
                    'telefono' => $pedido->sucursal->telefono ?? '',
                    'direccion' => $pedido->sucursal->direccion ?? '',
                ],
                'tipo' => $pedido->tipo->value,
                'tipo_etiqueta' => $pedido->tipo->etiqueta(),
                'estado' => $pedido->estado->value,
                'estado_etiqueta' => $pedido->estado->etiqueta(),
                'etiqueta_cliente' => $pedido->etiquetaCliente(),
                'direccion_entrega' => $pedido->direccion_entrega,
                'nombre_contacto' => $pedido->nombre_contacto ?? $pedido->cliente?->name,
                'telefono_contacto' => $pedido->telefono_contacto ?? $pedido->cliente?->telefono,
                'subtotal' => (float) $pedido->subtotal,
                'descuento' => (float) $pedido->descuento,
                'impuestos' => (float) $pedido->impuestos,
                'propina' => (float) $pedido->propina,
                'total' => (float) $pedido->total,
                'notas' => $pedido->notas,
                'fecha' => $pedido->fechaLocal(),
                'items' => $pedido->items->map(fn ($it) => [
                    'id' => $it->id,
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
}
