<?php

namespace App\Jobs;

use App\Events\PromocionPublicada;
use App\Events\PromocionRetirada;
use App\Models\Promocion;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublicarPromocionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?CarbonInterface $momento = null
    ) {}

    /**
     * Evalúa y actualiza la publicación de promociones según fecha y hora actual (DE-09, CA-20, RF-PRO-04).
     *
     * @return array{publicadas: int, retiradas: int}
     */
    public function handle(): array
    {
        $momento = $this->momento ?? now();
        $promociones = Promocion::withoutGlobalScopes()->where('activa', true)->get();

        $publicadas = 0;
        $retiradas = 0;

        foreach ($promociones as $promocion) {
            $vigente = $promocion->estaVigente($momento);

            if ($vigente && ! $promocion->publicada) {
                $promocion->update(['publicada' => true]);
                event(new PromocionPublicada($promocion));
                $publicadas++;
            } elseif (! $vigente && $promocion->publicada) {
                $promocion->update(['publicada' => false]);
                event(new PromocionRetirada($promocion->id, $promocion->sucursal_id));
                $retiradas++;
            }
        }

        return [
            'publicadas' => $publicadas,
            'retiradas' => $retiradas,
        ];
    }
}
