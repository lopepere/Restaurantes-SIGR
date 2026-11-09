<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CancelacionSolicitada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $solicitudData;

    public function __construct(
        public Pedido $pedido,
        public string $motivo,
        public ?string $solicitanteNombre = null,
        public ?string $solicitanteTelefono = null
    ) {
        $this->pedido->loadMissing(['cliente', 'mesero']);
        $cliente = $this->pedido->cliente;

        $this->solicitudData = [
            'pedido_id' => $this->pedido->id,
            'pedido_numero' => $this->pedido->numero,
            'sucursal_id' => $this->pedido->sucursal_id,
            'motivo' => $this->motivo,
            'solicitante_nombre' => $this->solicitanteNombre ?? ($cliente ? $cliente->nombre : ($this->pedido->nombre_contacto ?? 'Cliente')),
            'solicitante_telefono' => $this->solicitanteTelefono ?? ($cliente ? $cliente->telefono : ($this->pedido->telefono_contacto ?? '')),
            'total' => (float) $this->pedido->total,
            'alerta_sonora' => true,
            'alerta_urgente' => true,
            'solicitada_at' => now()->toISOString(),
        ];
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sucursal.'.$this->pedido->sucursal_id.'.pedidos'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CancelacionSolicitada';
    }
}
