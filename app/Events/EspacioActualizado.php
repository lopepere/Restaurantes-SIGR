<?php

namespace App\Events;

use App\Models\Espacio;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EspacioActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $espacioData;

    public function __construct(public Espacio $espacio)
    {
        $this->espacioData = [
            'id' => $this->espacio->id,
            'sucursal_id' => $this->espacio->sucursal_id,
            'nombre' => $this->espacio->nombre,
            'tipo' => $this->espacio->tipo,
            'capacidad' => $this->espacio->capacidad,
            'estado' => $this->espacio->estado->value,
            'estado_etiqueta' => $this->espacio->estado->etiqueta(),
            'activo' => $this->espacio->activo,
        ];
    }

    /**
     * Canal público donde se transmite la actualización de estado de mesas en sala (CA-24).
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sucursal.'.$this->espacio->sucursal_id.'.mesas'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'EspacioActualizado';
    }
}
