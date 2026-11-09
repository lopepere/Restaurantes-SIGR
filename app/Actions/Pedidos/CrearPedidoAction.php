<?php

namespace App\Actions\Pedidos;

use App\Actions\Usuarios\CrearClienteAction;
use App\Actions\Usuarios\InvitarClienteAction;
use App\Enums\EstadoPedido;
use App\Enums\OrigenUsuario;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
use App\Events\PedidoCreado;
use App\Events\ProductoAgotado;
use App\Models\ItemMenu;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\PedidoItem;
use App\Models\Promocion;
use App\Models\User;
use App\Notifications\PedidoConfirmadoNotification;
use App\Services\MonedaService;
use App\Services\TelefonoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearPedidoAction
{
    public function __construct(
        protected CrearClienteAction $crearClienteAction,
        protected InvitarClienteAction $invitarClienteAction
    ) {}

    /**
     * Crea un pedido validando existencias con lock de concurrencia (CA-27)
     * y gestionando cuentas para clientes autenticados, meseros o invitados (CA-01, CA-33).
     *
     * @param  array{
     *     sucursal_id: int,
     *     tipo: TipoPedido|string,
     *     items: array<int, array{item_menu_id: int, cantidad: float|int, notas?: string|null}>,
     *     cliente_id?: int|null,
     *     mesero_id?: int|null,
     *     espacio_id?: int|null,
     *     direccion_entrega?: string|null,
     *     telefono_contacto?: string|null,
     *     nombre_contacto?: string|null,
     *     email_contacto?: string|null,
     *     notas?: string|null,
     *     propina_tipo?: PropinaTipo|string|null,
     *     propina_porcentaje?: float|int|null,
     *     propina_valor?: float|int|null,
     * } $datos
     */
    public function handle(array $datos): Pedido
    {
        $sucursalId = (int) $datos['sucursal_id'];
        $tipo = $datos['tipo'] instanceof TipoPedido ? $datos['tipo'] : TipoPedido::from($datos['tipo']);

        $clienteId = isset($datos['cliente_id']) ? (int) $datos['cliente_id'] : null;
        $meseroId = isset($datos['mesero_id']) ? (int) $datos['mesero_id'] : null;
        $espacioId = isset($datos['espacio_id']) ? (int) $datos['espacio_id'] : null;

        $nombreContacto = $datos['nombre_contacto'] ?? null;
        $telefonoContacto = $datos['telefono_contacto'] ?? null;
        $emailContacto = $datos['email_contacto'] ?? null;
        $direccionEntrega = $datos['direccion_entrega'] ?? null;
        $notasGenerales = $datos['notas'] ?? null;

        // 1. Gestión de cliente no registrado / invitado (CA-01, CA-33, CA-37)
        if (! $clienteId && ! $meseroId && $emailContacto) {
            $emailLimpio = strtolower(trim($emailContacto));
            $telefonoNorm = $telefonoContacto ? TelefonoService::normalizar($telefonoContacto) : null;

            $usuarioExistente = User::withTrashed()
                ->where('email', $emailLimpio)
                ->orWhere(function ($q) use ($telefonoNorm) {
                    if ($telefonoNorm) {
                        $q->where('telefono', $telefonoNorm);
                    }
                })
                ->first();

            if ($usuarioExistente) {
                $clienteId = $usuarioExistente->id;
                // Si la cuenta no está activada, reenviamos la invitación (CA-37)
                if (! $usuarioExistente->password) {
                    $this->invitarClienteAction->handle($usuarioExistente);
                }
            } else {
                // Crear cuenta implícita con origen PEDIDO (RF-USR-01, CA-01)
                $nuevoCliente = $this->crearClienteAction->handle([
                    'nombre' => $nombreContacto ?: 'Cliente Express',
                    'email' => $emailLimpio,
                    'telefono' => $telefonoContacto ?: '3000000000',
                    'direccion' => $direccionEntrega ?: 'Dirección no provista',
                    'origen' => OrigenUsuario::PEDIDO,
                ]);

                $clienteId = $nuevoCliente->id;
            }
        }

        // Si es cliente autenticado y no envió dirección de domicilio, tomar la del perfil
        if ($clienteId && ! $direccionEntrega) {
            $cliente = User::find($clienteId);
            $direccionEntrega = $cliente?->direccion;
        }

        // Validación de entrega para domicilios
        if ($tipo === TipoPedido::DOMICILIO && empty($direccionEntrega)) {
            throw ValidationException::withMessages([
                'direccion_entrega' => 'La dirección de entrega es obligatoria para pedidos a domicilio.',
            ]);
        }

        if (empty($datos['items'])) {
            throw ValidationException::withMessages([
                'items' => 'El pedido debe contener al menos un producto.',
            ]);
        }

        // 2. Procesamiento transaccional con bloqueo de concurrencia (CA-27, RF-INV-02)
        return DB::transaction(function () use (
            $sucursalId,
            $tipo,
            $datos,
            $clienteId,
            $meseroId,
            $espacioId,
            $direccionEntrega,
            $telefonoContacto,
            $nombreContacto,
            $emailContacto,
            $notasGenerales
        ) {
            $numero = Pedido::generarNumero($sucursalId);

            $subtotalAcumulado = 0.00;
            $impuestosAcumulados = 0.00;
            $descuentoAcumulado = 0.00;
            $baseSinImpuestosAcumulada = 0.00;

            $itemsParaInsertar = [];

            // Obtener promociones vigentes de la sucursal para calcular precios dinámicos
            /** @var Collection<int, Promocion> $promocionesVigentes */
            $promocionesVigentes = Promocion::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalId)
                ->where('activa', true)
                ->where('publicada', true)
                ->get()
                ->filter(fn (Promocion $p) => $p->estaVigente());

            foreach ($datos['items'] as $itemData) {
                $itemId = (int) $itemData['item_menu_id'];
                $cantidad = max(1.0, (float) $itemData['cantidad']);
                $notasItem = $itemData['notas'] ?? null;

                /** @var ItemMenu|null $item */
                $item = ItemMenu::withoutGlobalScopes()
                    ->where('id', $itemId)
                    ->where('sucursal_id', $sucursalId)
                    ->first();

                if (! $item) {
                    throw ValidationException::withMessages([
                        'items' => "El producto con ID {$itemId} no existe en esta sucursal.",
                    ]);
                }

                if (! $item->disponible || ! $item->activo) {
                    throw ValidationException::withMessages([
                        'items' => "El producto {$item->nombre} no se encuentra disponible actualmente.",
                    ]);
                }

                // Control y decremento de existencias atómico con lockForUpdate (CA-27)
                $comboDesglose = null;
                if ($item->es_combo) {
                    $item->loadMissing(['comboItems.item']);
                    $desgloseArray = [];

                    foreach ($item->comboItems as $comboComponente) {
                        $compItem = $comboComponente->item;
                        if (! $compItem) {
                            continue;
                        }

                        $cantRequerida = (float) $comboComponente->cantidad * $cantidad;
                        $desgloseArray[] = [
                            'item_id' => $compItem->id,
                            'nombre' => $compItem->nombre,
                            'cantidad_por_combo' => (float) $comboComponente->cantidad,
                            'cantidad_total' => $cantRequerida,
                        ];

                        if ($compItem->controla_inventario) {
                            /** @var ItemMenu $compLocked */
                            $compLocked = ItemMenu::withoutGlobalScopes()
                                ->lockForUpdate()
                                ->findOrFail($compItem->id);

                            if ((float) $compLocked->existencia < $cantRequerida) {
                                throw ValidationException::withMessages([
                                    'items' => "Existencias insuficientes para el producto {$compLocked->nombre} que compone el combo {$item->nombre}.",
                                ]);
                            }

                            $compLocked->existencia = (float) $compLocked->existencia - $cantRequerida;
                            if ((float) $compLocked->existencia <= 0.0) {
                                $compLocked->disponible = false;
                                event(new ProductoAgotado($sucursalId, $compLocked->id, $compLocked->nombre));
                            }
                            $compLocked->save();
                        }
                    }

                    $comboDesglose = $desgloseArray;
                } elseif ($item->controla_inventario) {
                    /** @var ItemMenu $itemLocked */
                    $itemLocked = ItemMenu::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->findOrFail($item->id);

                    if ((float) $itemLocked->existencia < $cantidad) {
                        throw ValidationException::withMessages([
                            'items' => "Existencias insuficientes para el producto {$item->nombre}.",
                        ]);
                    }

                    $itemLocked->existencia = (float) $itemLocked->existencia - $cantidad;
                    if ((float) $itemLocked->existencia <= 0.0) {
                        $itemLocked->disponible = false;
                        event(new ProductoAgotado($sucursalId, $item->id, $item->nombre));
                    }
                    $itemLocked->save();
                }

                // Cálculo de precios y promociones
                $precioOriginal = (float) $item->precio;
                $precioEfectivo = $precioOriginal;

                foreach ($promocionesVigentes as $promo) {
                    if ($promo->aplicaAItem($item)) {
                        $precioConPromo = $promo->calcularPrecioPromocional($precioOriginal);
                        if ($precioConPromo < $precioEfectivo) {
                            $precioEfectivo = $precioConPromo;
                        }
                    }
                }

                $descuentoUnitario = max(0.00, $precioOriginal - $precioEfectivo);
                $descuentoLinea = MonedaService::redondear($descuentoUnitario * $cantidad);
                $totalLinea = MonedaService::redondear($precioEfectivo * $cantidad);
                $subtotalLinea = MonedaService::redondear($precioOriginal * $cantidad);

                // Desglose tributario (8% INC o respectivo)
                $desgloseImpuesto = MonedaService::desglosarImpuestoIncluido($precioEfectivo, $item->impuesto_porcentaje);
                $impuestoLinea = MonedaService::redondear($desgloseImpuesto['impuesto'] * $cantidad);
                $baseSinImpuestoLinea = MonedaService::redondear($desgloseImpuesto['base'] * $cantidad);

                $subtotalAcumulado += $subtotalLinea;
                $descuentoAcumulado += $descuentoLinea;
                $impuestosAcumulados += $impuestoLinea;
                $baseSinImpuestosAcumulada += $baseSinImpuestoLinea;

                $itemsParaInsertar[] = [
                    'item_menu_id' => $item->id,
                    'nombre_item' => $item->nombre,
                    'precio_unitario' => $precioOriginal,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotalLinea,
                    'impuesto_tipo' => $item->impuesto_tipo->value,
                    'impuesto_porcentaje' => (float) $item->impuesto_porcentaje,
                    'impuesto_monto' => $impuestoLinea,
                    'descuento_aplicado' => $descuentoLinea,
                    'total' => $totalLinea,
                    'notas' => $notasItem,
                    'combo_desglose' => $comboDesglose,
                ];
            }

            // 3. Cálculo de la propina opcional (SC-02, ERS 8.5)
            $propinaTipo = isset($datos['propina_tipo'])
                ? ($datos['propina_tipo'] instanceof PropinaTipo ? $datos['propina_tipo'] : PropinaTipo::from($datos['propina_tipo']))
                : PropinaTipo::NINGUNA;

            $propinaPorcentaje = isset($datos['propina_porcentaje']) ? (float) $datos['propina_porcentaje'] : null;
            $propinaValor = 0.00;

            if ($propinaTipo === PropinaTipo::PORCENTAJE && $propinaPorcentaje !== null && $propinaPorcentaje > 0) {
                $propinaValor = MonedaService::calcularPropina($baseSinImpuestosAcumulada, $propinaPorcentaje);
            } elseif ($propinaTipo === PropinaTipo::ABIERTA && isset($datos['propina_valor'])) {
                $propinaValor = max(0.00, MonedaService::redondear((float) $datos['propina_valor']));
            }

            $totalFinal = MonedaService::redondear($subtotalAcumulado - $descuentoAcumulado + $propinaValor);

            // 4. Creación del modelo Pedido
            $pedido = Pedido::create([
                'sucursal_id' => $sucursalId,
                'numero' => $numero,
                'cliente_id' => $clienteId,
                'mesero_id' => $meseroId,
                'espacio_id' => $espacioId,
                'tipo' => $tipo,
                'estado' => EstadoPedido::PENDIENTE,
                'direccion_entrega' => $direccionEntrega,
                'telefono_contacto' => $telefonoContacto,
                'nombre_contacto' => $nombreContacto,
                'notas' => $notasGenerales,
                'subtotal' => MonedaService::redondear($subtotalAcumulado),
                'descuento' => MonedaService::redondear($descuentoAcumulado),
                'impuestos' => MonedaService::redondear($impuestosAcumulados),
                'propina_tipo' => $propinaTipo,
                'propina_porcentaje' => $propinaPorcentaje,
                'propina' => $propinaValor,
                'total' => $totalFinal,
                'solicito_cancelacion' => false,
                'pagado' => false,
            ]);

            // 5. Creación de los ítems del pedido
            foreach ($itemsParaInsertar as $itemData) {
                $itemData['pedido_id'] = $pedido->id;
                PedidoItem::create($itemData);
            }

            // 6. Registro inicial en el historial de estados
            PedidoEstado::create([
                'pedido_id' => $pedido->id,
                'user_id' => auth()->id() ?? $clienteId,
                'estado_anterior' => null,
                'estado_nuevo' => EstadoPedido::PENDIENTE->value,
                'motivo' => 'Pedido creado exitosamente',
                'created_at' => now(),
            ]);

            // 7. Difusión por WebSockets en tiempo real (Laravel Reverb)
            event(new PedidoCreado($pedido));

            // 8. Notificación por correo electrónico encolada (RF-NOT-01)
            $cliente = $pedido->cliente;
            $destinatarioEmail = $cliente ? $cliente->email : $emailContacto;
            if ($destinatarioEmail) {
                $notifiable = $cliente ?? (new User(['email' => $destinatarioEmail, 'nombre' => $nombreContacto]));
                $notifiable->notify(new PedidoConfirmadoNotification($pedido));
            }

            return $pedido;
        });
    }
}
