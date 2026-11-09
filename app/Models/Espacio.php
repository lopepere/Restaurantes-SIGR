<?php

namespace App\Models;

use App\Enums\EstadoEspacio;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $nombre
 * @property string $tipo
 * @property int $capacidad
 * @property EstadoEspacio $estado
 * @property bool $activo
 */
class Espacio extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'espacios';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'tipo',
        'capacidad',
        'estado',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoEspacio::class,
            'capacidad' => 'integer',
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
     * @return HasMany<Pedido, $this>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'espacio_id');
    }
}
