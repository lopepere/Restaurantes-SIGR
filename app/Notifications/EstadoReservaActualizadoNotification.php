<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstadoReservaActualizadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reserva $reserva, public ?string $motivo = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->reserva->loadMissing(['sucursal']);

        $mail = (new MailMessage)
            ->subject("Actualización de reserva {$this->reserva->codigo}: {$this->reserva->estado->etiqueta()}")
            ->greeting('¡Hola, '.$this->reserva->nombre_contacto.'!')
            ->line("Tu reserva **{$this->reserva->codigo}** para el día **{$this->reserva->fecha->format('d/m/Y')}** ha cambiado a estado: **{$this->reserva->estado->etiqueta()}**.");

        if ($this->motivo) {
            $mail->line('**Motivo / Observación:** '.$this->motivo);
        }

        $mail->line('Gracias por tu preferencia.')
            ->salutation('El equipo de '.$this->reserva->sucursal->nombre);

        return $mail;
    }
}
