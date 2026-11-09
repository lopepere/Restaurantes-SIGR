<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use App\Notifications\InvitacionClienteNotification;
use Illuminate\Support\Facades\URL;

class InvitarClienteAction
{
    /**
     * Genera un enlace firmado temporal válido por 72 horas para definir la contraseña (DE-06, RN-USR-04, RF-USR-03).
     */
    public function handle(User $user): string
    {
        $urlFirmada = URL::temporarySignedRoute(
            'invitacion.aceptar',
            now()->addHours(72),
            ['user' => $user->id]
        );

        $user->update([
            'invitacion_enviada_at' => now(),
        ]);

        $user->notify(new InvitacionClienteNotification($urlFirmada));

        return $urlFirmada;
    }
}
