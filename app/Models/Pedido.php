<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
use App\Observers\PedidoObserver;
use App\Services\ConfiguracionService;
use App\Traits\Auditable;
use App\Traits\BelongsToSucursal;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property string $numero
 * @property int|null $cliente_id
 * @property int|null $mesero_id
 * @property int|null $espacio_id
 * @property TipoPedido $tipo
 * @property EstadoPedido $estado
 * @property string|null $direccion_entrega
 * @property string|null $telefono_contacto
 * @property string|null $nombre_contacto
 * @property string|null $notas
 * @property float|string $subtotal
 * @property float|string $descuento
 * @property float|string $impuestos
 * @property PropinaTipo $propina_tipo
 * @property float|string|null $propina_porcentaje
 * @property float|string $propina
 * @property float|string $total
 * @property bool $solicito_cancelacion
 * @property string|null $motivo_cancelacion
 * @property Carbon|null $cancelacion_solicitada_at
 * @property int|null $cancelado_por_id
 * @property bool $pagado
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, PedidoItem> $items
 * @property-read User|null $cliente
 * @property-read User|null $mesero
 * @property-read Collection<int, Pago> $pagos
 */
#[ObservedBy([PedidoObserver::class])]
class Pedido extends Model
{
    use Auditable, BelongsToSucursal, SoftDeletes;

    protected $table = 'pedidos';

    protected $fillable = [
        'sucursal_id',
        'numero',
        'cliente_id',
        'mesero_id',
        'espacio_id',
        'tipo',
        'estado',
        'direccion_entrega',
        'telefono_contacto',
        'nombre_contacto',
        'notas',
        'subtotal',
        'descuento',
        'impuestos',
        'propina_tipo',
        'propina_porcentaje',
        'propina',
        'total',
        'solicito_cancelacion',
        'motivo_cancelacion',
        'cancelacion_solicitada_at',
        'cancelado_por_id',
        'pagado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPedido::class,
            'estado' => EstadoPedido::class,
            'propina_tipo' => PropinaTipo::class,
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'impuestos' => 'decimal:2',
            'propina_porcentaje' => 'decimal:2',
            'propina' => 'decimal:2',
            'total' => 'decimal:2',
            'solicito_cancelacion' => 'boolean',
            'cancelacion_solicitada_at' => 'datetime',
            'pagado' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function mesero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mesero_id');
    }

    /**
     * @return BelongsTo<Espacio, $this>
     */
    public function espacio(): BelongsTo
    {
        return $this->belongsTo(Espacio::class, 'espacio_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_id');
    }

    /**
     * @return HasMany<PedidoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * @return HasMany<PedidoEstado, $this>
     */
    public function estados(): HasMany
    {
        return $this->hasMany(PedidoEstado::class);
    }

    /**
     * @return HasMany<SolicitudCancelacion, $this>
     */
    public function solicitudesCancelacion(): HasMany
    {
        return $this->hasMany(SolicitudCancelacion::class);
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Comprobante de venta emitido para este pedido (SC-03).
     *
     * @return HasOne<Comprobante, $this>
     */
    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class);
    }

    /**
     * Determina si el pedido está actualmente activo (no pagado ni cancelado).
     */
    public function estaActivo(): bool
    {
        return ! in_array($this->estado, [EstadoPedido::PAGADO, EstadoPedido::CANCELADO], true);
    }

    /**
     * Determina si el pedido está dentro de la ventana de edición configurable (CA-10).
     */
    public function estaEnVentanaEdicion(?int $minutos = null): bool
    {
        if ($minutos === null) {
            $configMinutos = ConfiguracionService::obtener('pedidos.ventana_edicion_minutos', $this->sucursal_id, 5);
            $minutos = is_numeric($configMinutos) ? (int) $configMinutos : 5;
        }

        return $this->created_at->diffInMinutes(now()) <= $minutos;
    }

    /**
     * Indica si un cliente puede editar notas del pedido.
     */
    public function puedeEditarNotas(): bool
    {
        return $this->estado === EstadoPedido::PENDIENTE && $this->estaEnVentanaEdicion();
    }

    /**
     * Indica si un mesero puede modificar los ítems del pedido.
     */
    public function puedeEditarItems(): bool
    {
        return $this->estado === EstadoPedido::PENDIENTE && $this->estaEnVentanaEdicion();
    }

    /**
     * Etiqueta amigable que ve el cliente («En curso», «Completado» o «Entregado»).
     */
    public function etiquetaCliente(): string
    {
        return $this->estado->etiquetaCliente($this->tipo);
    }

    /**
     * Convierte la fecha UTC a la zona horaria de la sucursal.
     */
    public function fechaLocal(?string $zonaHoraria = null): string
    {
        $tz = $zonaHoraria
            ?? ($this->relationLoaded('sucursal') ? $this->sucursal?->zona_horaria : null)
            ?? config('app.timezone', 'America/Bogota');

        return $this->created_at->copy()->setTimezone($tz)->format('d/m/Y H:i');
    }

    /**
     * Genera el número consecutivo único por sucursal (PED-{SUCURSAL_ID}-{YYYYMMDD}-{CONSECUTIVO}).
     */
    public static function generarNumero(int $sucursalId): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = sprintf('PED-%d-%s-', $sucursalId, $fecha);

        $ultimoNumero = static::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('numero', 'like', "{$prefijo}%")
            ->orderByDesc('id')
            ->value('numero');

        $consecutivo = 1;
        if ($ultimoNumero) {
            $partes = explode('-', (string) $ultimoNumero);
            $ultimoSec = (int) end($partes);
            $consecutivo = $ultimoSec + 1;
        }

        return sprintf('%s%04d', $prefijo, $consecutivo);
    }
}
