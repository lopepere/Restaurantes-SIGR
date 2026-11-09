<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitacionClienteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $urlInvitacion) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activa tu cuenta en SIGR')
            ->greeting('¡Hola, '.($notifiable->nombre ?? 'cliente').'!')
            ->line('Gracias por tu preferencia. Hemos creado tu cuenta en nuestra plataforma gastronómica.')
            ->line('Para definir tu contraseña y acceder al historial de tus pedidos, haz clic en el siguiente botón:')
            ->action('Definir Contraseña', $this->urlInvitacion)
            ->line('Este enlace de invitación vencerá en 72 horas.')
            ->salutation('Equipo SIGR');
    }
}
