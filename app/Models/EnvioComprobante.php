<?php

namespace App\Models;

use App\Enums\EstadoEnvioComprobante;
use App\Enums\TipoEnvioComprobante;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $comprobante_id
 * @property TipoEnvioComprobante $tipo
 * @property array<int, string> $destinatarios
 * @property string|null $mensaje
 * @property int|null $usuario_id
 * @property EstadoEnvioComprobante $estado
 * @property string|null $error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Comprobante $comprobante
 * @property-read User|null $usuario
 */
class EnvioComprobante extends Model
{
    protected $table = 'envios_comprobante';

    protected $fillable = [
        'comprobante_id',
        'tipo',
        'destinatarios',
        'mensaje',
        'usuario_id',
        'estado',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEnvioComprobante::class,
            'destinatarios' => 'array',
            'estado' => EstadoEnvioComprobante::class,
        ];
    }

    /**
     * @return BelongsTo<Comprobante, $this>
     */
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
