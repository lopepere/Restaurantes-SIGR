<?php

namespace App\Notifications;

use App\Models\Comprobante;
use App\Services\MonedaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComprobanteEmitidoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comprobante $comprobante) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->comprobante->loadMissing(['sucursal', 'pedido.items']);

        $verificacionUrl = url("/comprobante/verificar/{$this->comprobante->codigo_verificacion}");

        return (new MailMessage)
            ->subject("Comprobante de Venta {$this->comprobante->numero} — {$this->comprobante->sucursal->nombre}")
            ->greeting('¡Hola, '.($notifiable->nombre ?? $this->comprobante->cliente_nombre).'!')
            ->line("Tu pago ha sido procesado exitosamente en nuestra sucursal **{$this->comprobante->sucursal->nombre}**.")
            ->line("**Comprobante de Venta:** {$this->comprobante->numero}")
            ->line('**Código Único de Verificación (CUV):** '.$this->comprobante->codigo_verificacion)
            ->line('---')
            ->line('**Subtotal:** '.MonedaService::formatear($this->comprobante->subtotal))
            ->line('**Impuestos (INC/IVA):** '.MonedaService::formatear($this->comprobante->impuesto_total))
            ->when((float) $this->comprobante->propina > 0, fn ($m) => $m->line('**Propina voluntaria:** '.MonedaService::formatear($this->comprobante->propina)))
            ->line('**Total pagado:** '.MonedaService::formatear($this->comprobante->total))
            ->line('---')
            ->action('Verificar Comprobante en Línea', $verificacionUrl)
            ->line('Puedes consultar, descargar o imprimir tu comprobante cuando lo desees.')
            ->salutation('El equipo de '.$this->comprobante->sucursal->nombre);
    }
}
