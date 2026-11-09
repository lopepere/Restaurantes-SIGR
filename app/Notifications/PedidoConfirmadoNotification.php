<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Services\MonedaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PedidoConfirmadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Pedido $pedido) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->pedido->loadMissing(['sucursal', 'items']);

        $mail = (new MailMessage)
            ->subject("Pedido confirmado — {$this->pedido->numero}")
            ->greeting('¡Hola, '.($notifiable->nombre ?? $this->pedido->nombre_contacto ?? 'cliente').'!')
            ->line("Hemos recibido tu pedido **{$this->pedido->numero}** con éxito en nuestra sucursal **{$this->pedido->sucursal->nombre}**.")
            ->line('**Tipo de pedido:** '.$this->pedido->tipo->etiqueta());

        if ($this->pedido->direccion_entrega) {
            $mail->line('**Dirección de entrega:** '.$this->pedido->direccion_entrega);
        }

        $mail->line('---')
            ->line('**Detalle de tu orden:**');

        foreach ($this->pedido->items as $item) {
            $cant = (float) $item->cantidad;
            $precio = MonedaService::formatear($item->total);
            $mail->line("• {$cant}x {$item->nombre_item} — {$precio}");
        }

        $mail->line('---')
            ->line('**Total a pagar:** '.MonedaService::formatear($this->pedido->total))
            ->line('Te notificaremos cuando tu pedido cambie de estado.')
            ->salutation('El equipo de '.$this->pedido->sucursal->nombre);

        return $mail;
    }
}
