<?php

namespace App\Actions\Comprobantes;

use App\Contracts\FacturacionElectronicaGateway;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoEnvioComprobante;
use App\Enums\TipoEnvioComprobante;
use App\Models\Comprobante;
use App\Models\EnvioComprobante;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Notifications\ComprobanteEmitidoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerarComprobanteAction
{
    public function __construct(
        protected FacturacionElectronicaGateway $facturacionGateway
    ) {}

    /**
     * Genera el comprobante de venta inmutable FAC para un pedido pagado (SC-03, RF-FAC-01..06, CA-31).
     */
    public function handle(Pedido $pedido, ?string $metodoPago = null, ?User $actor = null): Comprobante
    {
        // Si ya existe comprobante para este pedido, retornarlo (1:1 inmutable)
        $comprobanteExistente = Comprobante::withoutGlobalScopes()
            ->where('pedido_id', $pedido->id)
            ->first();

        if ($comprobanteExistente) {
            return $comprobanteExistente;
        }

        $pedido->loadMissing(['sucursal', 'cliente', 'items.itemMenu', 'pagos']);

        return DB::transaction(function () use ($pedido, $metodoPago, $actor) {
            $numero = Comprobante::generarNumero($pedido->sucursal_id);
            $cuv = (string) Str::uuid();

            $clienteNombre = $pedido->cliente instanceof User
                ? $pedido->cliente->nombre
                : ($pedido->nombre_contacto ?? 'Consumidor Final');
            $clienteEmail = $pedido->cliente instanceof User
                ? $pedido->cliente->email
                : null;
            $clienteTelefono = $pedido->cliente instanceof User
                ? $pedido->cliente->telefono
                : $pedido->telefono_contacto;

            // Método de pago predominante
            $primerPago = $pedido->pagos->first();
            $metodo = $metodoPago ?? ($primerPago instanceof Pago ? $primerPago->metodo_pago->value : 'efectivo');

            // Generación de representación inmutable (HTML/Ticket 80 mm) y hash SHA-256
            $contenidoTicket = $this->renderizarTicketHtml($pedido, $numero, $cuv);
            $hashPdf = hash('sha256', $contenidoTicket);

            $comprobante = Comprobante::create([
                'sucursal_id' => $pedido->sucursal_id,
                'pedido_id' => $pedido->id,
                'numero' => $numero,
                'codigo_verificacion' => $cuv,
                'cliente_id' => $pedido->cliente_id,
                'cliente_nombre' => $clienteNombre,
                'cliente_documento' => null,
                'cliente_email' => $clienteEmail,
                'cliente_telefono' => $clienteTelefono,
                'subtotal' => (float) $pedido->subtotal,
                'descuento' => (float) $pedido->descuento,
                'impuesto_total' => (float) $pedido->impuestos,
                'propina' => (float) $pedido->propina,
                'total' => (float) $pedido->total,
                'metodo_pago' => $metodo,
                'hash_pdf' => $hashPdf,
                'estado' => EstadoComprobante::EMITIDO,
            ]);

            // Auditoría de emisión por la pasarela desacoplada
            $this->facturacionGateway->emitir($comprobante);

            // Registro en envíos
            if ($clienteEmail) {
                EnvioComprobante::create([
                    'comprobante_id' => $comprobante->id,
                    'tipo' => TipoEnvioComprobante::EMAIL_CLIENTE,
                    'destinatarios' => [$clienteEmail],
                    'mensaje' => 'Comprobante de venta generado automáticamente tras el pago.',
                    'usuario_id' => $actor ? $actor->id : auth()->id(),
                    'estado' => EstadoEnvioComprobante::ENVIADO,
                ]);

                // Envío de notificación por correo
                $destinatario = $pedido->cliente ?? new User([
                    'email' => $clienteEmail,
                    'nombre' => $clienteNombre,
                ]);
                $destinatario->notify(new ComprobanteEmitidoNotification($comprobante));
            } else {
                EnvioComprobante::create([
                    'comprobante_id' => $comprobante->id,
                    'tipo' => TipoEnvioComprobante::IMPRESION,
                    'destinatarios' => ['Tirilla física de sala'],
                    'mensaje' => 'Impresión en tirilla de 80 mm para comensal en mesa.',
                    'usuario_id' => $actor ? $actor->id : auth()->id(),
                    'estado' => EstadoEnvioComprobante::ENVIADO,
                ]);
            }

            return $comprobante;
        });
    }

    /**
     * Renderiza el contenido del ticket de venta en formato estándar de 80 mm.
     */
    public function renderizarTicketHtml(Pedido $pedido, string $numero, string $cuv): string
    {
        $sucursal = $pedido->sucursal;
        $fecha = now()->format('d/m/Y H:i');

        $html = "========================================\n";
        $html .= "          {$sucursal->nombre}          \n";
        $html .= "     {$sucursal->direccion}     \n";
        $html .= "        Tel: {$sucursal->telefono}       \n";
        $html .= "========================================\n";
        $html .= "COMPROBANTE DE VENTA: {$numero}\n";
        $html .= "CUV: {$cuv}\n";
        $html .= "Fecha: {$fecha}\n";
        $html .= "Pedido: #{$pedido->numero} ({$pedido->tipo->etiqueta()})\n";
        $clienteEtiqueta = $pedido->cliente instanceof User
            ? $pedido->cliente->nombre
            : ($pedido->nombre_contacto ?? 'Consumidor Final');
        $html .= 'Cliente: '.$clienteEtiqueta."\n";
        $html .= "----------------------------------------\n";
        $html .= "CANT  DESCRIPCION                 TOTAL \n";
        $html .= "----------------------------------------\n";

        foreach ($pedido->items as $item) {
            $cant = str_pad((string) (int) $item->cantidad, 4, ' ', STR_PAD_RIGHT);
            $nombre = Str::limit($item->nombre_item, 22);
            $nombrePad = str_pad($nombre, 24, ' ', STR_PAD_RIGHT);
            $subtotalStr = str_pad('$'.number_format((float) $item->subtotal, 0), 10, ' ', STR_PAD_LEFT);
            $html .= "{$cant} {$nombrePad} {$subtotalStr}\n";
        }

        $html .= "----------------------------------------\n";
        $html .= 'Subtotal:        $'.number_format((float) $pedido->subtotal, 2)."\n";
        if ((float) $pedido->descuento > 0) {
            $html .= 'Descuento:      -$'.number_format((float) $pedido->descuento, 2)."\n";
        }
        $html .= 'Impoconsumo/IVA: $'.number_format((float) $pedido->impuestos, 2)."\n";
        if ((float) $pedido->propina > 0) {
            $html .= 'Propina volunt.: $'.number_format((float) $pedido->propina, 2)."\n";
        }
        $html .= 'TOTAL A PAGAR:   $'.number_format((float) $pedido->total, 2)."\n";
        $html .= "========================================\n";
        $html .= "      ¡Gracias por su preferencia!      \n";
        $html .= "  Consulte en: /comprobante/verificar/{$cuv}\n";
        $html .= "========================================\n";

        return $html;
    }
}
