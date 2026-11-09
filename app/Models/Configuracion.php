<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuracion extends Model
{
    use Auditable;

    protected $table = 'configuraciones';

    protected $fillable = [
        'sucursal_id',
        'clave',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
