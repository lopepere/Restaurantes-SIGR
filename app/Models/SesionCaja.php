<?php

namespace App\Models;

use App\Enums\EstadoSesionCaja;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $caja_id
 * @property int $sucursal_id
 * @property int $user_id
 * @property Carbon $fecha_apertura
 * @property float|string $monto_apertura
 * @property Carbon|null $fecha_cierre
 * @property float|string|null $monto_cierre_declarado
 * @property float|string|null $monto_cierre_calculado
 * @property float|string|null $diferencia
 * @property EstadoSesionCaja $estado
 * @property array<string, int>|null $arqueo_desglose
 * @property string|null $observaciones
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Caja $caja
 * @property-read User $user
 * @property-read Collection<int, Pago> $pagos
 */
class SesionCaja extends Model
{
    use BelongsToSucursal;

    protected $table = 'sesiones_caja';

    protected $fillable = [
        'caja_id',
        'sucursal_id',
        'user_id',
        'fecha_apertura',
        'monto_apertura',
        'fecha_cierre',
        'monto_cierre_declarado',
        'monto_cierre_calculado',
        'diferencia',
        'estado',
        'arqueo_desglose',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'monto_apertura' => 'decimal:2',
            'monto_cierre_declarado' => 'decimal:2',
            'monto_cierre_calculado' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'estado' => EstadoSesionCaja::class,
            'arqueo_desglose' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Caja, $this>
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === EstadoSesionCaja::ABIERTA;
    }
}
