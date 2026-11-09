<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstacionCocina extends Model
{
    use Auditable, BelongsToSucursal;

    protected $table = 'estaciones_cocina';

    protected $fillable = [
        'sucursal_id',
        'user_id',
        'nombre',
        'codigo',
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
    public function cocinero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

