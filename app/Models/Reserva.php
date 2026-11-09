<?php

namespace App\Models;

use App\Enums\EstadoReserva;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $codigo
 * @property int|null $cliente_id
 * @property int|null $espacio_id
 * @property int|null $categoria_reserva_id
 * @property \Illuminate\Support\Carbon $fecha
 * @property string $hora_inicio
 * @property string $hora_fin
 * @property int $personas
 * @property EstadoReserva $estado
 * @property string $nombre_contacto
 * @property string $email_contacto
 * @property string $telefono_contacto
 * @property string|null $notas
 * @property string|null $motivo_cancelacion
 * @property int|null $cancelado_por_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Sucursal $sucursal
 * @property-read User|null $cliente
 * @property-read Espacio|null $espacio
 * @property-read CategoriaReserva|null $categoriaReserva
 * @property-read User|null $canceladoPor
 */
class Reserva extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'reservas';

    protected $fillable = [
        'sucursal_id',
        'codigo',
        'cliente_id',
        'espacio_id',
        'categoria_reserva_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'personas',
        'estado',
        'nombre_contacto',
        'email_contacto',
        'telefono_contacto',
        'notas',
        'motivo_cancelacion',
        'cancelado_por_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'personas' => 'integer',
            'estado' => EstadoReserva::class,
        ];
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * @return BelongsTo<Espacio, $this>
     */
    public function espacio(): BelongsTo
    {
        return $this->belongsTo(Espacio::class, 'espacio_id');
    }

    /**
     * @return BelongsTo<CategoriaReserva, $this>
     */
    public function categoriaReserva(): BelongsTo
    {
        return $this->belongsTo(CategoriaReserva::class, 'categoria_reserva_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_id');
    }

    /**
     * Determina si la reserva está activa (pendiente o confirmada).
     */
    public function estaActiva(): bool
    {
        return in_array($this->estado, [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA], true);
    }

    /**
     * Genera un código consecutivo único por sucursal (RES-{SUCURSAL_ID}-{YYYYMMDD}-{0001}).
     */
    public static function generarCodigo(int $sucursalId, ?string $fecha = null): string
    {
        $fechaStr = $fecha ? Carbon::parse($fecha)->format('Ymd') : now()->format('Ymd');
        $prefijo = sprintf('RES-%d-%s-', $sucursalId, $fechaStr);

        $ultimo = static::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('codigo', 'like', "{$prefijo}%")
            ->orderByDesc('id')
            ->value('codigo');

        $consecutivo = 1;
        if ($ultimo) {
            $partes = explode('-', (string) $ultimo);
            $consecutivo = ((int) end($partes)) + 1;
        }

        return sprintf('%s%04d', $prefijo, $consecutivo);
    }
}
