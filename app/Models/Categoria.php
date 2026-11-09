<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'categorias';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'slug',
        'descripcion',
        'orden',
        'icono',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activa' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * @return HasMany<ItemMenu, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemMenu::class, 'categoria_id')->orderBy('orden');
    }

    /**
     * @return BelongsToMany<Promocion, $this>
     */
    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_categoria');
    }
}
