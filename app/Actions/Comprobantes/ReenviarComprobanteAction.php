<?php

namespace App\Actions\Comprobantes;

use App\Enums\EstadoEnvioComprobante;
use App\Enums\TipoEnvioComprobante;
use App\Models\Comprobante;
use App\Models\EnvioComprobante;
use App\Models\User;
use App\Notifications\ComprobanteEmitidoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ReenviarComprobanteAction
{
    /**
     * Reenvía un comprobante de venta al cliente o hasta 3 correos designados (CA-31).
     * Aplica límite de velocidad de 10 reenvíos por hora por usuario.
     *
     * @param  array<int, string>  $correos
     */
    public function handle(
        Comprobante $comprobante,
        array $correos,
        ?string $mensaje = null,
        ?User $actor = null
    ): EnvioComprobante {
        $actorId = $actor ? $actor->id : (auth()->id() ?? 0);
        $rateLimitKey = "reenvio_comprobante:{$actorId}";

        // Restricción de seguridad: 10 reenvíos por hora (Prompt Maestro)
        if ($actorId && RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $segundosRestantes = RateLimiter::availableIn($rateLimitKey);
            $minutos = (int) ceil($segundosRestantes / 60);

            throw ValidationException::withMessages([
                'correos' => "Has superado el límite de 10 envíos por hora. Intenta nuevamente en {$minutos} minutos.",
            ]);
        }

        // Limitar a máximo 3 correos designados
        $correosValidos = array_values(array_unique(array_slice($correos, 0, 3)));
        if (empty($correosValidos)) {
            throw ValidationException::withMessages([
                'correos' => 'Debe indicar al menos un correo electrónico válido.',
            ]);
        }

        foreach ($correosValidos as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'correos' => "El correo electrónico '{$email}' no tiene un formato válido.",
                ]);
            }
        }

        if ($actorId) {
            RateLimiter::hit($rateLimitKey, 3600);
        }

        // Despachar notificación
        foreach ($correosValidos as $email) {
            Notification::route('mail', $email)->notify(new ComprobanteEmitidoNotification($comprobante));
        }

        return EnvioComprobante::create([
            'comprobante_id' => $comprobante->id,
            'tipo' => TipoEnvioComprobante::EMAIL_DESIGNADO,
            'destinatarios' => $correosValidos,
            'mensaje' => $mensaje,
            'usuario_id' => $actor ? $actor->id : auth()->id(),
            'estado' => EstadoEnvioComprobante::ENVIADO,
        ]);
    }
}
