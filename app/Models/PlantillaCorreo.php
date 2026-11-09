<?php

namespace App\Models;

use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaCorreo extends Model
{
    use BelongsToSucursal;

    protected $table = 'plantillas_correo';

    protected $fillable = [
        'sucursal_id',
        'clave',
        'nombre',
        'asunto',
        'cuerpo_html',
        'variables_disponibles',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'variables_disponibles' => 'array',
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
}
