<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pedido_id
 * @property int|null $solicitado_por_id
 * @property int|null $resuelto_por_id
 * @property string $motivo
 * @property string $estado
 * @property string|null $respuesta_admin
 * @property Carbon $solicitada_at
 * @property Carbon|null $resuelta_at
 */
class SolicitudCancelacion extends Model
{
    protected $table = 'solicitudes_cancelacion';

    protected $fillable = [
        'pedido_id',
        'solicitado_por_id',
        'resuelto_por_id',
        'motivo',
        'estado',
        'respuesta_admin',
        'solicitada_at',
        'resuelta_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'solicitada_at' => 'datetime',
            'resuelta_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por_id');
    }
}
