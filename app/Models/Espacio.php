<?php

namespace App\Models;

use App\Enums\EstadoEspacio;
use App\Observers\EspacioObserver;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $nombre
 * @property string $tipo
 * @property int|null $tipo_mesa_id
 * @property int $capacidad
 * @property EstadoEspacio $estado
 * @property bool $activo
 * @property-read TipoMesa|null $tipoMesa
 */
#[ObservedBy([EspacioObserver::class])]
class Espacio extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'espacios';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'tipo',
        'tipo_mesa_id',
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
     * @return BelongsTo<TipoMesa, $this>
     */
    public function tipoMesa(): BelongsTo
    {
        return $this->belongsTo(TipoMesa::class, 'tipo_mesa_id');
    }

    /**
     * @return HasMany<Pedido, $this>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'espacio_id');
    }

    /**
     * @return HasMany<Reserva, $this>
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'espacio_id');
    }
}
