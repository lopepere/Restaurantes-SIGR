<?php

namespace App\Models;

use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int $dia_semana
 * @property string $hora_apertura
 * @property string $hora_cierre
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class HorarioAtencion extends Model
{
    use BelongsToSucursal;

    protected $table = 'horarios_atencion';

    protected $fillable = [
        'sucursal_id',
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'activo' => 'boolean',
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
     * Nombre en español del día de la semana (0: Domingo ... 6: Sábado).
     */
    public function nombreDia(): string
    {
        return match ($this->dia_semana) {
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            default => 'Día desconocido',
        };
    }
}
