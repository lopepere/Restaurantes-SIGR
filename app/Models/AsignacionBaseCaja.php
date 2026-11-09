<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int $caja_id
 * @property int|null $user_id
 * @property int|null $asignado_por_id
 * @property int|null $editado_por_id
 * @property Carbon $fecha
 * @property float|string $monto
 * @property string|null $observaciones
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Caja|null $caja
 * @property-read User|null $cajero
 * @property-read User|null $asignadoPor
 * @property-read User|null $editadoPor
 */
class AsignacionBaseCaja extends Model
{
    use Auditable, BelongsToSucursal;

    protected $table = 'asignaciones_base_caja';

    protected $fillable = [
        'sucursal_id',
        'caja_id',
        'user_id',
        'asignado_por_id',
        'editado_por_id',
        'fecha',
        'monto',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
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
     * @return BelongsTo<Caja, $this>
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por_id');
    }
}
