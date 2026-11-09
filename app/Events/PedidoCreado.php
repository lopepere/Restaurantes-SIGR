<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $pedidoData;

    public function __construct(public Pedido $pedido)
    {
        $this->pedido->loadMissing(['cliente', 'mesero', 'espacio', 'items']);
        $cliente = $this->pedido->cliente;
        $mesero = $this->pedido->mesero;
        $espacio = $this->pedido->espacio;

        $this->pedidoData = [
            'id' => $this->pedido->id,
            'numero' => $this->pedido->numero,
            'sucursal_id' => $this->pedido->sucursal_id,
            'tipo' => $this->pedido->tipo->value,
            'tipo_etiqueta' => $this->pedido->tipo->etiqueta(),
            'estado' => $this->pedido->estado->value,
            'estado_etiqueta' => $this->pedido->estado->etiqueta(),
            'etiqueta_cliente' => $this->pedido->etiquetaCliente(),
            'total' => (float) $this->pedido->total,
            'subtotal' => (float) $this->pedido->subtotal,
            'impuestos' => (float) $this->pedido->impuestos,
            'propina' => (float) $this->pedido->propina,
            'cliente_nombre' => $cliente ? $cliente->nombre : ($this->pedido->nombre_contacto ?? 'Cliente'),
            'cliente_telefono' => $cliente ? $cliente->telefono : ($this->pedido->telefono_contacto ?? ''),
            'mesero_nombre' => $mesero ? $mesero->nombre : null,
            'mesa' => $espacio ? $espacio->nombre : null,
            'direccion_entrega' => $this->pedido->direccion_entrega,
            'notas' => $this->pedido->notas,
            'items_count' => $this->pedido->items->count(),
            'created_at' => $this->pedido->created_at->toISOString(),
            'alerta_sonora' => true,
        ];
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $canales = [
            new PrivateChannel('sucursal.'.$this->pedido->sucursal_id.'.pedidos'),
        ];

        if ($this->pedido->cliente_id) {
            $canales[] = new PrivateChannel('cliente.'.$this->pedido->cliente_id.'.pedidos');
        }

        return $canales;
    }

    public function broadcastAs(): string
    {
        return 'PedidoCreado';
    }
}
