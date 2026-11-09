<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'cajas';

    protected $fillable = [
        'sucursal_id',
        'user_id',
        'nombre',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<SesionCaja, $this>
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionCaja::class);
    }

    /**
     * @return HasMany<AsignacionBaseCaja, $this>
     */
    public function asignacionesBase(): HasMany
    {
        return $this->hasMany(AsignacionBaseCaja::class);
    }
}
