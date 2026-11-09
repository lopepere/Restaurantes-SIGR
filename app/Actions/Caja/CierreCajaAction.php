<?php

namespace App\Actions\Caja;

use App\Enums\EstadoSesionCaja;
use App\Enums\MetodoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\SesionCaja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreCajaAction
{
    /**
     * Cierra el turno de caja, realiza el arqueo ciego o asistido y consolida propinas por mesero (RF-CAJ-05..08, CA-30).
     *
     * @param  array<string, int>|null  $desgloseEfectivo
     * @return array{
     *     sesion: SesionCaja,
     *     resumen: array<string, mixed>,
     *     propinas_por_mesero: array<int, array{mesero_id: int|null, mesero_nombre: string, total_propinas: float}>
     * }
     */
    public function handle(
        SesionCaja $sesion,
        float $montoCierreDeclarado,
        ?array $desgloseEfectivo = null,
        ?string $observaciones = null,
        ?User $actor = null
    ): array {
        if ($sesion->estado === EstadoSesionCaja::CERRADA) {
            throw ValidationException::withMessages([
                'sesion' => 'Esta sesión de caja ya se encuentra cerrada.',
            ]);
        }

        return DB::transaction(function () use ($sesion, $montoCierreDeclarado, $desgloseEfectivo, $observaciones) {
            // Pagos en efectivo ingresados en esta sesión
            $montoEfectivo = (float) Pago::where('sesion_caja_id', $sesion->id)
                ->where('metodo_pago', MetodoPago::EFECTIVO)
                ->sum('monto');

            // Pagos electrónicos (tarjetas, transferencias)
            $montoElectronico = (float) Pago::where('sesion_caja_id', $sesion->id)
                ->where('metodo_pago', '!=', MetodoPago::EFECTIVO)
                ->sum('monto');

            $montoCalculado = (float) $sesion->monto_apertura + $montoEfectivo;
            $diferencia = $montoCierreDeclarado - $montoCalculado;

            // Consolidación de propinas recaudadas por mesero
            $pedidosDeLaSesion = Pedido::withoutGlobalScopes()
                ->whereHas('pagos', fn ($q) => $q->where('sesion_caja_id', $sesion->id))
                ->with(['mesero'])
                ->get();

            $propinasAgrupadas = [];
            foreach ($pedidosDeLaSesion as $ped) {
                if ((float) $ped->propina > 0) {
                    $mId = $ped->mesero_id ?? 0;
                    if (! isset($propinasAgrupadas[$mId])) {
                        $propinasAgrupadas[$mId] = [
                            'mesero_id' => $ped->mesero_id,
                            'mesero_nombre' => $ped->mesero instanceof User ? $ped->mesero->nombre : 'Sin mesero asignado / Directo',
                            'total_propinas' => 0.0,
                        ];
                    }
                    $propinasAgrupadas[$mId]['total_propinas'] += (float) $ped->propina;
                }
            }

            $sesion->fecha_cierre = now();
            $sesion->monto_cierre_declarado = $montoCierreDeclarado;
            $sesion->monto_cierre_calculado = $montoCalculado;
            $sesion->diferencia = $diferencia;
            $sesion->arqueo_desglose = $desgloseEfectivo;
            $sesion->observaciones = $observaciones;
            $sesion->estado = EstadoSesionCaja::CERRADA;
            $sesion->save();

            $resumen = [
                'monto_apertura' => (float) $sesion->monto_apertura,
                'ventas_efectivo' => $montoEfectivo,
                'ventas_electronicas' => $montoElectronico,
                'total_ventas' => $montoEfectivo + $montoElectronico,
                'efectivo_esperado' => $montoCalculado,
                'efectivo_declarado' => $montoCierreDeclarado,
                'diferencia' => $diferencia,
                'estado_descuadre' => $diferencia == 0.0 ? 'cuadrada' : ($diferencia > 0 ? 'sobrante' : 'faltante'),
            ];

            return [
                'sesion' => $sesion,
                'resumen' => $resumen,
                'propinas_por_mesero' => array_values($propinasAgrupadas),
            ];
        });
    }
}
