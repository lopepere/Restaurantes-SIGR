<?php

namespace App\Models;

use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $compra_id
 * @property int $sucursal_id
 * @property int $user_id
 * @property Carbon $fecha_devolucion
 * @property string $motivo
 * @property string $tipo
 * @property array<int, mixed>|null $items_devueltos
 * @property float|string $total_devuelto
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Compra|null $compra
 * @property-read Sucursal $sucursal
 * @property-read User|null $user
 */
class DevolucionCompra extends Model
{
    use BelongsToSucursal;

    protected $table = 'devoluciones_compra';

    protected $fillable = [
        'compra_id',
        'sucursal_id',
        'user_id',
        'fecha_devolucion',
        'motivo',
        'tipo',
        'items_devueltos',
        'total_devuelto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_devolucion' => 'datetime',
            'items_devueltos' => 'array',
            'total_devuelto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Compra, $this>
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
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
}
