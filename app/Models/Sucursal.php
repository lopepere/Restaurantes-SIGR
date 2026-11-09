<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'slug',
        'direccion',
        'telefono',
        'logo',
        'zona_horaria',
        'moneda',
        'idioma',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * Usuarios asignados a esta sucursal.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sucursal_user')
            ->withTimestamps();
    }

    /**
     * Configuraciones específicas de esta sucursal.
     *
     * @return HasMany<Configuracion, $this>
     */
    public function configuraciones(): HasMany
    {
        return $this->hasMany(Configuracion::class, 'sucursal_id');
    }
}
