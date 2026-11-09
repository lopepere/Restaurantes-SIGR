<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sucursalId,
        public string $tipo = 'completo',
        public ?string $mensaje = null
    ) {}

    /**
     * Canal público donde se transmite la actualización del menú.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('menu.'.$this->sucursalId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MenuActualizado';
    }
}
