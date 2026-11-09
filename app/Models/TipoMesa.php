<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $nombre
 * @property int $capacidad_minima
 * @property int $capacidad_maxima
 * @property bool $es_abierta
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class TipoMesa extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'tipos_mesa';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'capacidad_minima',
        'capacidad_maxima',
        'es_abierta',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidad_minima' => 'integer',
            'capacidad_maxima' => 'integer',
            'es_abierta' => 'boolean',
            'activo' => 'boolean',
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
     * @return HasMany<Espacio, $this>
     */
    public function espacios(): HasMany
    {
        return $this->hasMany(Espacio::class, 'tipo_mesa_id');
    }
}
