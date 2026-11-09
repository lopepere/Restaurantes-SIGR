<?php

namespace App\Events;

use App\Models\Reserva;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservaActualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $reservaData;

    public function __construct(public Reserva $reserva, public ?string $motivo = null)
    {
        $this->reserva->loadMissing(['sucursal', 'espacio', 'categoriaReserva']);

        $this->reservaData = [
            'id' => $this->reserva->id,
            'codigo' => $this->reserva->codigo,
            'sucursal_id' => $this->reserva->sucursal_id,
            'fecha' => $this->reserva->fecha->format('Y-m-d'),
            'hora_inicio' => substr($this->reserva->hora_inicio, 0, 5),
            'hora_fin' => substr($this->reserva->hora_fin, 0, 5),
            'personas' => $this->reserva->personas,
            'estado' => $this->reserva->estado->value,
            'estado_etiqueta' => $this->reserva->estado->etiqueta(),
            'nombre_contacto' => $this->reserva->nombre_contacto,
            'mesa' => $this->reserva->espacio?->nombre,
            'motivo' => $this->motivo,
            'updated_at' => $this->reserva->updated_at->toISOString(),
            'alerta_sonora' => true,
        ];
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $canales = [
            new PrivateChannel('sucursal.'.$this->reserva->sucursal_id.'.reservas'),
        ];

        if ($this->reserva->cliente_id) {
            $canales[] = new PrivateChannel('cliente.'.$this->reserva->cliente_id.'.reservas');
        }

        return $canales;
    }

    public function broadcastAs(): string
    {
        return 'ReservaActualizada';
    }
}
