<?php

namespace App\Models;

use App\Observers\SucursalObserver;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SucursalObserver::class])]
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

    /**
     * Categorías del menú de esta sucursal.
     *
     * @return HasMany<Categoria, $this>
     */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'sucursal_id')->orderBy('orden');
    }

    /**
     * Ítems del menú de esta sucursal.
     *
     * @return HasMany<ItemMenu, $this>
     */
    public function itemsMenu(): HasMany
    {
        return $this->hasMany(ItemMenu::class, 'sucursal_id')->orderBy('orden');
    }

    /**
     * Promociones de esta sucursal.
     *
     * @return HasMany<Promocion, $this>
     */
    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class, 'sucursal_id');
    }
}
