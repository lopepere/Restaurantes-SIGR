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
        'es_sistema',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activa' => 'boolean',
            'es_sistema' => 'boolean',
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

    /**
     * Garantiza que exista la categoría inmutable del sistema "Sin categorizar" para la sucursal dada.
     */
    public static function asegurarSinCategorizar(int $sucursalId): self
    {
        $categoria = static::withoutGlobalScopes()
            ->withTrashed()
            ->where('sucursal_id', $sucursalId)
            ->where('slug', 'sin-categorizar')
            ->first();

        if ($categoria) {
            if ($categoria->trashed()) {
                $categoria->restore();
            }
            if (! $categoria->es_sistema || ! $categoria->activa) {
                $categoria->update(['es_sistema' => true, 'activa' => true]);
            }

            return $categoria;
        }

        return static::withoutGlobalScopes()->create([
            'sucursal_id' => $sucursalId,
            'nombre' => 'Sin categorizar',
            'slug' => 'sin-categorizar',
            'descripcion' => 'Categoría por defecto del sistema para productos sin categoría asignada.',
            'orden' => 999,
            'icono' => '📦',
            'activa' => true,
            'es_sistema' => true,
        ]);
    }
}
