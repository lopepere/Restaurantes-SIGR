<?php

namespace App\Models;

use App\Enums\TipoImpuesto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pedido_id
 * @property int|null $item_menu_id
 * @property string $nombre_item
 * @property string $precio_unitario
 * @property string $cantidad
 * @property string $subtotal
 * @property TipoImpuesto $impuesto_tipo
 * @property string $impuesto_porcentaje
 * @property string $impuesto_monto
 * @property string $descuento_aplicado
 * @property string $total
 * @property string|null $notas
 * @property array<string, mixed>|null $combo_desglose
 */
class PedidoItem extends Model
{
    protected $table = 'pedido_items';

    protected $fillable = [
        'pedido_id',
        'item_menu_id',
        'nombre_item',
        'precio_unitario',
        'cantidad',
        'subtotal',
        'impuesto_tipo',
        'impuesto_porcentaje',
        'impuesto_monto',
        'descuento_aplicado',
        'total',
        'anulado',
        'motivo_anulacion',
        'anulado_por_id',
        'notas',
        'combo_desglose',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'cantidad' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'impuesto_tipo' => TipoImpuesto::class,
            'impuesto_porcentaje' => 'decimal:2',
            'impuesto_monto' => 'decimal:2',
            'descuento_aplicado' => 'decimal:2',
            'total' => 'decimal:2',
            'anulado' => 'boolean',
            'combo_desglose' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
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
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }
}
