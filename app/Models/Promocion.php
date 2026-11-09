<?php

namespace App\Models;

use App\Enums\TipoBeneficioPromocion;
use App\Services\MonedaService;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $nombre
 * @property string $slug
 * @property string|null $descripcion
 * @property TipoBeneficioPromocion $tipo_beneficio
 * @property string $valor_beneficio
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property array<int, int>|null $dias_semana
 * @property string|null $hora_inicio
 * @property string|null $hora_fin
 * @property bool $activa
 * @property bool $publicada
 * @property string|null $imagen
 * @property bool $aplica_a_todo
 */
class Promocion extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'promociones';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'slug',
        'descripcion',
        'tipo_beneficio',
        'valor_beneficio',
        'fecha_inicio',
        'fecha_fin',
        'dias_semana',
        'hora_inicio',
        'hora_fin',
        'activa',
        'publicada',
        'imagen',
        'aplica_a_todo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_beneficio' => TipoBeneficioPromocion::class,
            'valor_beneficio' => 'decimal:2',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'dias_semana' => 'array',
            'activa' => 'boolean',
            'publicada' => 'boolean',
            'aplica_a_todo' => 'boolean',
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
     * @return BelongsToMany<ItemMenu, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ItemMenu::class, 'promocion_item');
    }

    /**
     * @return BelongsToMany<Categoria, $this>
     */
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'promocion_categoria');
    }

    /**
     * Evalúa si la promoción está vigente en un momento dado según calendario, días y horas (DE-09, CA-20).
     */
    public function estaVigente(?CarbonInterface $momento = null): bool
    {
        if (! $this->activa) {
            return false;
        }

        $momento = $momento ?? now();

        // 1. Rango de fechas
        if ($momento->lt($this->fecha_inicio) || $momento->gt($this->fecha_fin)) {
            return false;
        }

        // 2. Días de la semana (0 = Domingo, 1 = Lunes, ..., 6 = Sábado)
        if (! empty($this->dias_semana)) {
            $diaActual = $momento->dayOfWeek;
            if (! in_array($diaActual, $this->dias_semana, true)) {
                return false;
            }
        }

        // 3. Franja horaria diaria
        if ($this->hora_inicio && $this->hora_fin) {
            $horaActual = $momento->format('H:i:s');
            $horaInicio = substr($this->hora_inicio, 0, 8);
            $horaFin = substr($this->hora_fin, 0, 8);

            if ($horaActual < $horaInicio || $horaActual > $horaFin) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula el precio final con descuento aplicado para un precio original.
     */
    public function calcularPrecioPromocional(float $precioOriginal): float
    {
        $beneficio = (float) $this->valor_beneficio;

        $precioFinal = match ($this->tipo_beneficio) {
            TipoBeneficioPromocion::PORCENTAJE => $precioOriginal * (1 - ($beneficio / 100)),
            TipoBeneficioPromocion::VALOR_FIJO => max(0.0, $precioOriginal - $beneficio),
            TipoBeneficioPromocion::PRECIO_ESPECIAL => $beneficio,
        };

        return MonedaService::redondear($precioFinal);
    }

    /**
     * Determina si la promoción aplica a un ítem específico del menú.
     */
    public function aplicaAItem(ItemMenu $item): bool
    {
        if ($this->aplica_a_todo) {
            return true;
        }

        if ($this->items()->where('items_menu.id', $item->id)->exists()) {
            return true;
        }

        if ($item->categoria_id && $this->categorias()->where('categorias.id', $item->categoria_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Retorna una descripción corta y atractiva del beneficio.
     */
    public function beneficioTexto(): string
    {
        return match ($this->tipo_beneficio) {
            TipoBeneficioPromocion::PORCENTAJE => (int) $this->valor_beneficio.'% OFF',
            TipoBeneficioPromocion::VALOR_FIJO => MonedaService::formatear($this->valor_beneficio).' OFF',
            TipoBeneficioPromocion::PRECIO_ESPECIAL => 'Precio especial '.MonedaService::formatear($this->valor_beneficio),
        };
    }

    /**
     * @param  Builder<Promocion>  $query
     */
    public function scopePublicadas(Builder $query): void
    {
        $query->where('publicada', true)->where('activa', true);
    }
}
