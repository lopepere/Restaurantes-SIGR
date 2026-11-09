<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $razon_social
 * @property string|null $nit
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $direccion
 * @property string|null $contacto_nombre
 * @property string|null $contacto_telefono
 * @property string|null $notas
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Sucursal $sucursal
 * @property-read Collection<int, Compra> $compras
 */
class Proveedor extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'sucursal_id',
        'razon_social',
        'nit',
        'telefono',
        'email',
        'direccion',
        'contacto_nombre',
        'contacto_telefono',
        'notas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
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
     * @return HasMany<Compra, $this>
     */
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }
}
