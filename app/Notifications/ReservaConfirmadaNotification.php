<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaConfirmadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reserva $reserva) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->reserva->loadMissing(['sucursal', 'espacio', 'categoriaReserva']);

        $mail = (new MailMessage)
            ->subject("Reserva registrada — {$this->reserva->codigo}")
            ->greeting('¡Hola, '.$this->reserva->nombre_contacto.'!')
            ->line("Hemos recibido tu reserva **{$this->reserva->codigo}** para nuestra sucursal **{$this->reserva->sucursal->nombre}**.")
            ->line('**Fecha:** '.$this->reserva->fecha->format('d/m/Y'))
            ->line('**Hora:** '.substr($this->reserva->hora_inicio, 0, 5).' a '.substr($this->reserva->hora_fin, 0, 5))
            ->line('**Personas:** '.$this->reserva->personas)
            ->line('**Estado:** '.$this->reserva->estado->etiqueta());

        if ($this->reserva->espacio) {
            $mail->line('**Mesa asignada:** '.$this->reserva->espacio->nombre);
        }

        if ($this->reserva->notas) {
            $mail->line('**Peticiones especiales:** '.$this->reserva->notas);
        }

        $mail->line('Te esperamos con el mejor servicio.')
            ->salutation('El equipo de '.$this->reserva->sucursal->nombre);

        return $mail;
    }
}
