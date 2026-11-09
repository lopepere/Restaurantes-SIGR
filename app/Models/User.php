<?php

namespace App\Models;

use App\Enums\OrigenUsuario;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

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
}
