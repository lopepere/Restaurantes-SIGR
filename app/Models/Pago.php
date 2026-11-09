<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pedido_id
 * @property int|null $sesion_caja_id
 * @property int $user_id
 * @property float|string $monto
 * @property float|string $propina_valor
 * @property MetodoPago $metodo_pago
 * @property float|string|null $monto_recibido
 * @property float|string|null $cambio_devuelto
 * @property string|null $referencia_transaccion
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Pedido $pedido
 * @property-read SesionCaja|null $sesionCaja
 * @property-read User $user
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'sesion_caja_id',
        'user_id',
        'monto',
        'propina_valor',
        'metodo_pago',
        'monto_recibido',
        'cambio_devuelto',
        'referencia_transaccion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'propina_valor' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'cambio_devuelto' => 'decimal:2',
            'metodo_pago' => MetodoPago::class,
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
     * @return BelongsTo<SesionCaja, $this>
     */
    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
