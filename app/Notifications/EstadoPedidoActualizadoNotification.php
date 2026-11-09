<?php

namespace App\Notifications;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstadoPedidoActualizadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Pedido $pedido,
        public ?string $motivo = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->pedido->loadMissing(['sucursal']);

        $estadoEtiqueta = $this->pedido->estado->etiqueta();
        $etiquetaCliente = $this->pedido->etiquetaCliente();

        $mail = (new MailMessage)
            ->subject("Actualización de tu pedido {$this->pedido->numero}: {$estadoEtiqueta}")
            ->greeting('¡Hola, '.($notifiable->nombre ?? $this->pedido->nombre_contacto ?? 'cliente').'!')
            ->line("Tu pedido **{$this->pedido->numero}** ha cambiado a estado: **{$estadoEtiqueta}** ({$etiquetaCliente}).");

        if ($this->motivo) {
            $mail->line('**Detalle:** '.$this->motivo);
        }

        $mail->line('Gracias por tu confianza.')
            ->salutation('El equipo de '.$this->pedido->sucursal->nombre);

        return $mail;
    }
}
