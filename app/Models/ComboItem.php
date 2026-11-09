<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    protected $table = 'combo_items';

    protected $fillable = [
        'combo_id',
        'item_id',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ItemMenu, $this>
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(ItemMenu::class, 'combo_id');
    }

    /**
     * @return BelongsTo<ItemMenu, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemMenu::class, 'item_id');
    }
}
