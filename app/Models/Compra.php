<?php

namespace App\Models;

use App\Enums\EstadoCompra;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int $proveedor_id
 * @property int $user_id
 * @property string $numero_factura
 * @property Carbon $fecha_compra
 * @property float|string $subtotal
 * @property float|string $impuesto_total
 * @property float|string $total
 * @property EstadoCompra $estado
 * @property string|null $notas
 * @property int|null $anulado_por_id
 * @property string|null $motivo_anulacion
 * @property Carbon|null $anulado_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Proveedor $proveedor
 * @property-read Collection<int, CompraItem> $items
 */
class Compra extends Model
{
    use Auditable, BelongsToSucursal;

    protected $table = 'compras';

    protected $fillable = [
        'sucursal_id',
        'proveedor_id',
        'user_id',
        'numero_factura',
        'fecha_compra',
        'subtotal',
        'impuesto_total',
        'total',
        'estado',
        'notas',
        'anulado_por_id',
        'motivo_anulacion',
        'anulado_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_compra' => 'date',
            'subtotal' => 'decimal:2',
            'impuesto_total' => 'decimal:2',
            'total' => 'decimal:2',
            'estado' => EstadoCompra::class,
            'anulado_at' => 'datetime',
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
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }

    /**
     * @return HasMany<CompraItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }

    /**
     * @return HasMany<DevolucionCompra, $this>
     */
    public function devoluciones(): HasMany
    {
        return $this->hasMany(DevolucionCompra::class);
    }
}
