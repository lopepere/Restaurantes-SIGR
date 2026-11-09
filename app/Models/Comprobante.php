<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use App\Traits\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $sucursal_id
 * @property int $pedido_id
 * @property string $numero
 * @property string $codigo_verificacion
 * @property int|null $cliente_id
 * @property string $cliente_nombre
 * @property string|null $cliente_documento
 * @property string|null $cliente_email
 * @property string|null $cliente_telefono
 * @property float|string $subtotal
 * @property float|string $descuento
 * @property float|string $impuesto_total
 * @property float|string $propina
 * @property float|string $total
 * @property string $metodo_pago
 * @property string|null $hash_pdf
 * @property string|null $pdf_path
 * @property EstadoComprobante $estado
 * @property int|null $anulado_por_id
 * @property string|null $motivo_anulacion
 * @property Carbon|null $anulado_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Sucursal $sucursal
 * @property-read Pedido $pedido
 * @property-read User|null $cliente
 * @property-read User|null $anuladoPor
 * @property-read Collection<int, EnvioComprobante> $envios
 */
class Comprobante extends Model
{
    use BelongsToSucursal;

    protected $table = 'comprobantes';

    protected $fillable = [
        'sucursal_id',
        'pedido_id',
        'numero',
        'codigo_verificacion',
        'cliente_id',
        'cliente_nombre',
        'cliente_documento',
        'cliente_email',
        'cliente_telefono',
        'subtotal',
        'descuento',
        'impuesto_total',
        'propina',
        'total',
        'metodo_pago',
        'hash_pdf',
        'pdf_path',
        'estado',
        'anulado_por_id',
        'motivo_anulacion',
        'anulado_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'impuesto_total' => 'decimal:2',
            'propina' => 'decimal:2',
            'total' => 'decimal:2',
            'estado' => EstadoComprobante::class,
            'anulado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Comprobante $comprobante) {
            if (empty($comprobante->codigo_verificacion)) {
                $comprobante->codigo_verificacion = (string) Str::uuid();
            }
        });
    }

    /**
     * Genera el consecutivo único por sucursal: FAC-{SUCURSAL_ID}-{YYYYMMDD}-{0001}.
     */
    public static function generarNumero(int $sucursalId, ?string $fecha = null): string
    {
        $fechaCarbon = $fecha ? Carbon::parse($fecha) : Carbon::today();
        $prefijoFecha = $fechaCarbon->format('Ymd');
        $prefijo = "FAC-{$sucursalId}-{$prefijoFecha}-";

        $ultimoNumero = self::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->where('numero', 'like', "{$prefijo}%")
            ->orderByDesc('id')
            ->value('numero');

        $consecutivo = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $consecutivo = ((int) end($partes)) + 1;
        }

        return $prefijo.str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
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
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }

    /**
     * @return HasMany<EnvioComprobante, $this>
     */
    public function envios(): HasMany
    {
        return $this->hasMany(EnvioComprobante::class);
    }

    public function estaEmitido(): bool
    {
        return $this->estado === EstadoComprobante::EMITIDO;
    }
}
