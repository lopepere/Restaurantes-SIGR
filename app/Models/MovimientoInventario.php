<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int|null $item_menu_id
 * @property TipoMovimiento $tipo
 * @property float|string $cantidad
 * @property float|string $costo_unitario
 * @property float|string $costo_total
 * @property float|string $saldo_cantidad
 * @property float|string $saldo_costo
 * @property string|null $referencia_tipo
 * @property int|null $referencia_id
 * @property string|null $motivo
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Sucursal $sucursal
 * @property-read ItemMenu|null $itemMenu
 * @property-read User|null $user
 */
class MovimientoInventario extends Model
{
    use BelongsToSucursal;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'sucursal_id',
        'item_menu_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'saldo_cantidad',
        'saldo_costo',
        'referencia_tipo',
        'referencia_id',
        'motivo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
            'costo_total' => 'decimal:2',
            'saldo_cantidad' => 'decimal:2',
            'saldo_costo' => 'decimal:2',
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
     * @return BelongsTo<ItemMenu, $this>
     */
    public function itemMenu(): BelongsTo
    {
        return $this->belongsTo(ItemMenu::class, 'item_menu_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function referencia(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'referencia_tipo', 'referencia_id');
    }
}
