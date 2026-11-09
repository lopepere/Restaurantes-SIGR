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
        'acepta_domicilios',
        'acepta_para_llevar',
        'acepta_en_sitio',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'acepta_domicilios' => 'boolean',
            'acepta_para_llevar' => 'boolean',
            'acepta_en_sitio' => 'boolean',
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

    /**
     * Espacios y mesas de esta sucursal.
     *
     * @return HasMany<Espacio, $this>
     */
    public function espacios(): HasMany
    {
        return $this->hasMany(Espacio::class, 'sucursal_id');
    }

    /**
     * Pedidos de esta sucursal.
     *
     * @return HasMany<Pedido, $this>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'sucursal_id');
    }

    /**
     * Tipos de mesa configurados en esta sucursal.
     *
     * @return HasMany<TipoMesa, $this>
     */
    public function tiposMesa(): HasMany
    {
        return $this->hasMany(TipoMesa::class, 'sucursal_id');
    }

    /**
     * Horarios de atención configurados en esta sucursal.
     *
     * @return HasMany<HorarioAtencion, $this>
     */
    public function horariosAtencion(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class, 'sucursal_id');
    }

    /**
     * Categorías de reserva de esta sucursal.
     *
     * @return HasMany<CategoriaReserva, $this>
     */
    public function categoriasReserva(): HasMany
    {
        return $this->hasMany(CategoriaReserva::class, 'sucursal_id');
    }

    /**
     * Reservas realizadas en esta sucursal.
     *
     * @return HasMany<Reserva, $this>
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'sucursal_id');
    }

    /**
     * Proveedores registrados en esta sucursal.
     *
     * @return HasMany<Proveedor, $this>
     */
    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'sucursal_id');
    }

    /**
     * Compras registradas en esta sucursal.
     *
     * @return HasMany<Compra, $this>
     */
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'sucursal_id');
    }

    /**
     * Cajas de esta sucursal.
     *
     * @return HasMany<Caja, $this>
     */
    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'sucursal_id');
    }

    /**
     * Sesiones de caja de esta sucursal.
     *
     * @return HasMany<SesionCaja, $this>
     */
    public function sesionesCaja(): HasMany
    {
        return $this->hasMany(SesionCaja::class, 'sucursal_id');
    }

    /**
     * Comprobantes de venta FAC emitidos por esta sucursal.
     *
     * @return HasMany<Comprobante, $this>
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'sucursal_id');
    }

    /**
     * Movimientos de inventario registrados en esta sucursal.
     *
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'sucursal_id');
    }

    /**
     * Plantillas de correo personalizadas de esta sucursal.
     *
     * @return HasMany<PlantillaCorreo, $this>
     */
    public function plantillasCorreo(): HasMany
    {
        return $this->hasMany(PlantillaCorreo::class, 'sucursal_id');
    }
}
