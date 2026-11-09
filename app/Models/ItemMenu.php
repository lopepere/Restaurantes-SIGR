<?php

namespace App\Models;

use App\Enums\TipoImpuesto;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int $categoria_id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property float|string $precio
 * @property TipoImpuesto $impuesto_tipo
 * @property float|string $impuesto_porcentaje
 * @property string|null $imagen
 * @property int $orden
 * @property bool $es_combo
 * @property bool $controla_inventario
 * @property float|string $existencia
 * @property float|string $existencia_minima
 * @property bool $disponible
 * @property bool $activo
 * @property-read Categoria $categoria
 * @property-read Collection<int, ComboItem> $comboItems
 */
class ItemMenu extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'items_menu';

    protected $fillable = [
        'sucursal_id',
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'precio',
        'impuesto_tipo',
        'impuesto_porcentaje',
        'imagen',
        'orden',
        'es_combo',
        'controla_inventario',
        'existencia',
        'existencia_minima',
        'disponible',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'impuesto_tipo' => TipoImpuesto::class,
            'impuesto_porcentaje' => 'decimal:2',
            'orden' => 'integer',
            'es_combo' => 'boolean',
            'controla_inventario' => 'boolean',
            'existencia' => 'decimal:2',
            'existencia_minima' => 'decimal:2',
            'disponible' => 'boolean',
            'activo' => 'boolean',
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
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relación con los registros de la tabla combo_items cuando este item es un combo.
     *
     * @return HasMany<ComboItem, $this>
     */
    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'combo_id');
    }

    /**
     * Productos que componen este combo.
     *
     * @return BelongsToMany<ItemMenu, $this>
     */
    public function itemsDelCombo(): BelongsToMany
    {
        return $this->belongsToMany(ItemMenu::class, 'combo_items', 'combo_id', 'item_id')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /**
     * Combos en los que este ítem participa como componente.
     *
     * @return BelongsToMany<ItemMenu, $this>
     */
    public function combosDondeAparece(): BelongsToMany
    {
        return $this->belongsToMany(ItemMenu::class, 'combo_items', 'item_id', 'combo_id')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Promocion, $this>
     */
    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_item');
    }

    /**
     * Scope para productos activos en el menú.
     *
     * @param  Builder<ItemMenu>  $query
     */
    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * Scope para productos marcados como disponibles.
     *
     * @param  Builder<ItemMenu>  $query
     */
    public function scopeDisponibles(Builder $query): void
    {
        $query->where('disponible', true);
    }

    /**
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'item_menu_id');
    }

    /**
     * Determina si el producto está agotado para venta (por falta de existencia o marcado no disponible).
     */
    public function estaAgotado(): bool
    {
        if (! $this->disponible) {
            return true;
        }

        if ($this->controla_inventario && (float) $this->existencia <= 0.0) {
            return true;
        }

        return false;
    }
}
