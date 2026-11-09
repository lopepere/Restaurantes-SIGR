<?php

namespace App\Events;

use App\Models\Promocion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromocionPublicada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Promocion $promocion
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('menu.'.$this->promocion->sucursal_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PromocionPublicada';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->promocion->id,
            'nombre' => $this->promocion->nombre,
            'descripcion' => $this->promocion->descripcion,
            'tipo_beneficio' => $this->promocion->tipo_beneficio->value,
            'valor_beneficio' => $this->promocion->valor_beneficio,
            'fecha_fin' => $this->promocion->fecha_fin->toIso8601String(),
            'aplica_a_todo' => $this->promocion->aplica_a_todo,
        ];
    }
}
