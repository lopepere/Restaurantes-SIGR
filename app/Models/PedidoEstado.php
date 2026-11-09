<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pedido_id
 * @property int|null $user_id
 * @property string|null $estado_anterior
 * @property string $estado_nuevo
 * @property string|null $motivo
 * @property Carbon $created_at
 */
class PedidoEstado extends Model
{
    public $timestamps = false;

    protected $table = 'pedido_estados';

    protected $fillable = [
        'pedido_id',
        'user_id',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
