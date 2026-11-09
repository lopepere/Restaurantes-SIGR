<?php

namespace App\Actions\Reservas;

use App\Actions\Usuarios\CrearClienteAction;
use App\Actions\Usuarios\InvitarClienteAction;
use App\Enums\EstadoReserva;
use App\Enums\OrigenUsuario;
use App\Events\ReservaCreada;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ReservaConfirmadaNotification;
use App\Services\ConfiguracionService;
use App\Services\DisponibilidadService;
use App\Services\TelefonoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearReservaAction
{
    public function __construct(
        protected CrearClienteAction $crearClienteAction,
        protected InvitarClienteAction $invitarClienteAction
    ) {}

    /**
     * Crea una reserva validando disponibilidad y parámetros por sucursal (CA-12, CA-22, Prompt A.6).
     *
     * @param  array{
     *     sucursal_id: int,
     *     fecha: string,
     *     hora_inicio: string,
     *     personas: int,
     *     cliente_id?: int|null,
     *     espacio_id?: int|null,
     *     categoria_reserva_id?: int|null,
     *     nombre_contacto: string,
     *     email_contacto: string,
     *     telefono_contacto: string,
     *     notas?: string|null,
     * } $datos
     */
    public function handle(array $datos): Reserva
    {
        $sucursalId = (int) $datos['sucursal_id'];
        $fecha = $datos['fecha'];
        $horaInicio = substr($datos['hora_inicio'], 0, 5);
        $personas = max(1, (int) $datos['personas']);

        $clienteId = isset($datos['cliente_id']) ? (int) $datos['cliente_id'] : null;
        $espacioId = isset($datos['espacio_id']) ? (int) $datos['espacio_id'] : null;
        $categoriaReservaId = isset($datos['categoria_reserva_id']) ? (int) $datos['categoria_reserva_id'] : null;

        $nombreContacto = trim($datos['nombre_contacto']);
        $emailContacto = strtolower(trim($datos['email_contacto']));
        $telefonoContacto = trim($datos['telefono_contacto']);
        $notas = $datos['notas'] ?? null;

        // 1. Validaciones de ventana de anticipación
        $anticipacionMinHoras = (int) ConfiguracionService::obtener('reservas.anticipacion_minima_horas', $sucursalId, 2);
        $anticipacionMaxDias = (int) ConfiguracionService::obtener('reservas.anticipacion_maxima_dias', $sucursalId, 60);

        $momentoReserva = Carbon::parse("{$fecha} {$horaInicio}");
        $ahora = now();

        if ($momentoReserva->isPast() && $momentoReserva->diffInMinutes($ahora) > 10) {
            throw ValidationException::withMessages([
                'fecha' => 'No es posible realizar una reserva en una fecha u hora pasada.',
            ]);
        }

        if ($momentoReserva->greaterThan($ahora->copy()->addDays($anticipacionMaxDias))) {
            throw ValidationException::withMessages([
                'fecha' => "Las reservas solo se pueden programar con un máximo de {$anticipacionMaxDias} días de anticipación.",
            ]);
        }

        if ($momentoReserva->isToday() && $momentoReserva->lessThan($ahora->copy()->addHours($anticipacionMinHoras))) {
            throw ValidationException::withMessages([
                'hora_inicio' => "Se requiere un mínimo de {$anticipacionMinHoras} horas de anticipación para reservas el mismo día.",
            ]);
        }

        // 2. Cálculo de hora de finalización según duración parametrizada
        $duracionMinutos = (int) ConfiguracionService::obtener('reservas.duracion_minutos', $sucursalId, 120);
        $horaFin = $momentoReserva->copy()->addMinutes($duracionMinutos)->format('H:i:s');

        // 3. Verificación de disponibilidad y asignación de mesa
        if (! $espacioId) {
            $espacioAsignado = DisponibilidadService::asignarMejorEspacio($sucursalId, $fecha, $horaInicio, $horaFin, $personas);
            if (! $espacioAsignado) {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'No hay mesas o espacios disponibles para la fecha, hora y cantidad de comensales seleccionados.',
                ]);
            }
            $espacioId = $espacioAsignado->id;
        }

        // 4. Gestión de cuenta para comensal no registrado (CA-12, RF-USR-01, CA-01, CA-37)
        if (! $clienteId && $emailContacto) {
            $telefonoNorm = TelefonoService::normalizar($telefonoContacto);

            $usuarioExistente = User::withTrashed()
                ->where('email', $emailContacto)
                ->orWhere(function ($q) use ($telefonoNorm) {
                    if ($telefonoNorm) {
                        $q->where('telefono', $telefonoNorm);
                    }
                })
                ->first();

            if ($usuarioExistente) {
                $clienteId = $usuarioExistente->id;
                if (! $usuarioExistente->password) {
                    $this->invitarClienteAction->handle($usuarioExistente);
                }
            } else {
                $nuevoCliente = $this->crearClienteAction->handle([
                    'nombre' => $nombreContacto,
                    'email' => $emailContacto,
                    'telefono' => $telefonoContacto,
                    'direccion' => 'Cliente de reserva',
                    'origen' => OrigenUsuario::RESERVA,
                ]);

                $clienteId = $nuevoCliente->id;
            }
        }

        // 5. Evaluación de aprobación automática (CA-12)
        $aprobacionAutomatica = (bool) (ConfiguracionService::obtener('reservas.aprobacion_automatica', $sucursalId, false)
            ?: ConfiguracionService::obtener('reserva.aprobacion_automatica', $sucursalId, false));
        $estadoInicial = $aprobacionAutomatica ? EstadoReserva::CONFIRMADA : EstadoReserva::PENDIENTE;

        // 6. Creación transaccional
        return DB::transaction(function () use (
            $sucursalId,
            $fecha,
            $horaInicio,
            $horaFin,
            $personas,
            $estadoInicial,
            $clienteId,
            $espacioId,
            $categoriaReservaId,
            $nombreContacto,
            $emailContacto,
            $telefonoContacto,
            $notas
        ) {
            $codigo = Reserva::generarCodigo($sucursalId, $fecha);

            $reserva = Reserva::create([
                'sucursal_id' => $sucursalId,
                'codigo' => $codigo,
                'cliente_id' => $clienteId,
                'espacio_id' => $espacioId,
                'categoria_reserva_id' => $categoriaReservaId,
                'fecha' => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'personas' => $personas,
                'estado' => $estadoInicial,
                'nombre_contacto' => $nombreContacto,
                'email_contacto' => $emailContacto,
                'telefono_contacto' => $telefonoContacto,
                'notas' => $notas,
            ]);

            // Difusión en tiempo real por Laravel Reverb
            event(new ReservaCreada($reserva));

            // Notificación por correo electrónico encolada
            $destinatario = $reserva->cliente ?? new User(['email' => $emailContacto, 'nombre' => $nombreContacto]);
            $destinatario->notify(new ReservaConfirmadaNotification($reserva));

            return $reserva;
        });
    }
}
