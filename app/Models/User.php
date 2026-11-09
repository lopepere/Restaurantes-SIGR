<?php

namespace App\Models;

use App\Enums\OrigenUsuario;
use App\Traits\Auditable;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property string $password
 * @property string|null $avatar
 * @property string $idioma
 * @property bool $activo
 * @property OrigenUsuario $origen
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'direccion',
        'password',
        'avatar',
        'idioma',
        'activo',
        'origen',
        'email_verified_at',
        'invitacion_token',
        'invitacion_enviada_at',
        'invitacion_aceptada_at',
        'politica_aceptada_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitacion_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'origen' => OrigenUsuario::class,
            'email_verified_at' => 'datetime',
            'invitacion_enviada_at' => 'datetime',
            'invitacion_aceptada_at' => 'datetime',
            'politica_aceptada_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Mapeo bidireccional entre la propiedad convencional de Laravel $user->name y $user->nombre.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['nombre'] ?? $value,
            set: fn ($value) => ['nombre' => $value],
        );
    }

    /**
     * Sucursales a las que tiene asignado acceso el usuario.
     *
     * @return BelongsToMany<Sucursal, $this>
     */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'sucursal_user')
            ->withTimestamps();
    }

    /**
     * Retorna los IDs de las sucursales asignadas al usuario.
     * Si es superadministrador, retorna todas las sucursales existentes.
     *
     * @return array<int, int>
     */
    public function sucursalIds(): array
    {
        if ($this->hasRole('super_admin')) {
            /** @var array<int, int> $allIds */
            $allIds = Sucursal::pluck('id')->all();

            return $allIds;
        }

        /** @var array<int, int> $assignedIds */
        $assignedIds = $this->sucursales()->pluck('sucursales.id')->all();

        return $assignedIds;
    }

    /**
     * Verifica si el usuario tiene asignada una sucursal específica.
     */
    public function tieneAccesoASucursal(int|string|Sucursal $sucursal): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $id = $sucursal instanceof Sucursal ? $sucursal->id : (int) $sucursal;

        return in_array($id, $this->sucursalIds(), true);
    }

    /**
     * @return HasMany<Pedido, $this>
     */
    public function pedidosComoCliente(): HasMany
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    /**
     * @return HasMany<Pedido, $this>
     */
    public function pedidosComoMesero(): HasMany
    {
        return $this->hasMany(Pedido::class, 'mesero_id');
    }

    /**
     * Comprobantes asociados a este usuario como cliente.
     *
     * @return HasMany<Comprobante, $this>
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'cliente_id');
    }

    /**
     * Sesiones de caja abiertas o cerradas por este cajero/usuario.
     *
     * @return HasMany<SesionCaja, $this>
     */
    public function sesionesCaja(): HasMany
    {
        return $this->hasMany(SesionCaja::class, 'user_id');
    }

    /**
     * Retorna la URL del dashboard correspondiente al rol principal del usuario.
     */
    public function dashboardUrl(): string
    {
        if ($this->hasRole('super_admin')) {
            return '/superadmin';
        }

        if ($this->hasRole('admin')) {
            return '/admin';
        }

        if ($this->hasRole('mesero')) {
            return '/mesero';
        }

        return '/cliente';
    }
}
