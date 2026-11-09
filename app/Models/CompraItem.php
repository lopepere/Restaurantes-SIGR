<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $compra_id
 * @property int|null $item_menu_id
 * @property string $descripcion
 * @property float|string $cantidad
 * @property float|string $costo_unitario
 * @property float|string $factor_conversion
 * @property float|string $impuesto_porcentaje
 * @property float|string $subtotal
 * @property float|string $impuesto_valor
 * @property float|string $total
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Compra $compra
 * @property-read ItemMenu|null $itemMenu
 */
class CompraItem extends Model
{
    protected $table = 'compra_items';

    protected $fillable = [
        'compra_id',
        'item_menu_id',
        'descripcion',
        'cantidad',
        'costo_unitario',
        'factor_conversion',
        'impuesto_porcentaje',
        'subtotal',
        'impuesto_valor',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
            'factor_conversion' => 'decimal:4',
            'impuesto_porcentaje' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'impuesto_valor' => 'decimal:2',
            'total' => 'decimal:2',
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
     * @return BelongsTo<ItemMenu, $this>
     */
    public function itemMenu(): BelongsTo
    {
        return $this->belongsTo(ItemMenu::class, 'item_menu_id');
    }
}
