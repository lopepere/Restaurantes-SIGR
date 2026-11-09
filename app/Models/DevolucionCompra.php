<?php

namespace App\Models;

use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'total_devuelto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_devolucion' => 'datetime',
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
