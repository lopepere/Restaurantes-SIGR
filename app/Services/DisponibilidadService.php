<?php

namespace App\Services;

use App\Enums\EstadoEspacio;
use App\Enums\EstadoPedido;
use App\Enums\EstadoReserva;
use App\Models\Espacio;
use App\Models\HorarioAtencion;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DisponibilidadService
{
    /**
     * Calcula las franjas horarias disponibles para una fecha y cantidad de personas (CA-22, Prompt A.6).
     * Descuenta reservas que se solapan y mesas ocupadas por pedidos activos.
     * La última franja termina antes de la hora de cierre.
     *
     * @return array<int, array{
     *     hora_inicio: string,
     *     hora_fin: string,
     *     disponible: bool,
     *     mesas_disponibles_count: int,
     *     espacio_sugerido_id: int|null
     * }>
     */
    public static function franjas(Sucursal|int $sucursal, string $fecha, int $personas = 2): array
    {
        $sucursalId = $sucursal instanceof Sucursal ? $sucursal->id : $sucursal;
        $carbonFecha = Carbon::parse($fecha);
        $diaSemana = $carbonFecha->dayOfWeek; // 0: Domingo, 1: Lunes, etc.

        // 1. Horario de atención del día
        $horario = HorarioAtencion::where('sucursal_id', $sucursalId)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->first();

        // Si no hay horario configurado para este día, el restaurante está cerrado
        if (! $horario) {
            return [];
        }

        // 2. Parámetros de reserva de la sucursal (o globales)
        $duracionMinutos = (int) ConfiguracionService::obtener('reservas.duracion_minutos', $sucursalId, 120);
        $intervaloMinutos = (int) ConfiguracionService::obtener('reservas.intervalo_minutos', $sucursalId, 30);
        $anticipacionMinHoras = (int) ConfiguracionService::obtener('reservas.anticipacion_minima_horas', $sucursalId, 2);

        $apertura = Carbon::parse("{$fecha} {$horario->hora_apertura}");
        $cierre = Carbon::parse("{$fecha} {$horario->hora_cierre}");

        // Si el cierre es pasada la medianoche (ej: 02:00)
        if ($cierre->lessThanOrEqualTo($apertura)) {
            $cierre->addDay();
        }

        // 3. Obtener mesas y espacios aptos para la capacidad solicitada
        /** @var Collection<int, Espacio> $espaciosCandidatos */
        $espaciosCandidatos = Espacio::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->where('estado', '!=', EstadoEspacio::FUERA_SERVICIO)
            ->with(['tipoMesa'])
            ->get()
            ->filter(function (Espacio $espacio) use ($personas) {
                if ($espacio->tipoMesa && $espacio->tipoMesa->es_abierta) {
                    return $personas >= $espacio->tipoMesa->capacidad_minima;
                }

                return $espacio->capacidad >= $personas;
            })
            ->sortBy('capacidad')
            ->values();

        if ($espaciosCandidatos->isEmpty()) {
            return [];
        }

        // 4. Reservas activas del día en esta sucursal (PENDIENTE o CONFIRMADA)
        $reservasDelDia = Reserva::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA])
            ->whereNotNull('espacio_id')
            ->get();

        // 5. Pedidos activos en mesas hoy (para descontar mesas ocupadas físicamente)
        $esHoy = $carbonFecha->isToday();
        $mesasConPedidosActivos = $esHoy
            ? Pedido::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalId)
                ->whereNotNull('espacio_id')
                ->whereNotIn('estado', [EstadoPedido::PAGADO, EstadoPedido::CANCELADO])
                ->pluck('espacio_id')
                ->all()
            : [];

        // 6. Generación y evaluación de franjas horarias
        $franjas = [];
        $momentoActual = now();
        $cursor = $apertura->copy();

        while (true) {
            $finFranja = $cursor->copy()->addMinutes($duracionMinutos);

            // La última franja debe terminar antes o a la hora de cierre
            if ($finFranja->greaterThan($cierre)) {
                break;
            }

            $inicioStr = $cursor->format('H:i:s');
            $finStr = $finFranja->format('H:i:s');

            // Verificar anticipación mínima si la reserva es para hoy
            $esValidoPorTiempo = true;
            if ($esHoy && $cursor->lessThan($momentoActual->copy()->addHours($anticipacionMinHoras))) {
                $esValidoPorTiempo = false;
            }

            // Evaluar qué espacios están libres en esta franja
            $espaciosLibres = $espaciosCandidatos->filter(function (Espacio $espacio) use (
                $reservasDelDia,
                $mesasConPedidosActivos,
                $inicioStr,
                $finStr,
                $esHoy
            ) {
                // Descontar si está ocupado por pedido activo en sala hoy
                if ($esHoy && in_array($espacio->id, $mesasConPedidosActivos, true)) {
                    return false;
                }

                // Descontar si tiene otra reserva solapada en esta franja
                $haySolapamiento = $reservasDelDia->first(function (Reserva $res) use ($espacio, $inicioStr, $finStr) {
                    if ($res->espacio_id !== $espacio->id) {
                        return false;
                    }

                    // Solapamiento: inicio_reserva < fin_franja AND fin_reserva > inicio_franja
                    return $res->hora_inicio < $finStr && $res->hora_fin > $inicioStr;
                });

                return ! $haySolapamiento;
            });

            $disponible = $esValidoPorTiempo && $espaciosLibres->isNotEmpty();
            $mejorEspacio = $espaciosLibres->first();

            $motivo = null;
            if (! $esValidoPorTiempo) {
                $motivo = 'Anticipación mínima requerida';
            } elseif ($espaciosLibres->isEmpty()) {
                $motivo = 'Sin mesas disponibles';
            }

            $franjas[] = [
                'hora_inicio' => $cursor->format('H:i'),
                'hora_fin' => $finFranja->format('H:i'),
                'disponible' => $disponible,
                'motivo' => $motivo,
                'mesas_disponibles_count' => $espaciosLibres->count(),
                'espacio_sugerido_id' => $mejorEspacio ? $mejorEspacio->id : null,
            ];

            $cursor->addMinutes($intervaloMinutos);
        }

        return $franjas;
    }

    /**
     * Encuentra el mejor espacio disponible para una reserva concreta.
     */
    public static function asignarMejorEspacio(
        int $sucursalId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        int $personas
    ): ?Espacio {
        $franjas = self::franjas($sucursalId, $fecha, $personas);
        $horaCorta = substr($horaInicio, 0, 5);

        foreach ($franjas as $f) {
            if ($f['hora_inicio'] === $horaCorta && $f['disponible'] && $f['espacio_sugerido_id']) {
                return Espacio::find($f['espacio_sugerido_id']);
            }
        }

        return null;
    }
}
