<?php

namespace App\Http\Controllers\Public;

use App\Actions\Comprobantes\GenerarComprobanteAction;
use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ComprobantePublicoController extends Controller
{
    /**
     * Consulta pública de autenticidad de un comprobante de venta por CUV (SC-03, RF-FAC-04).
     */
    public function verificar(string $cuv): Response
    {
        $comprobante = Comprobante::withoutGlobalScopes()
            ->where('codigo_verificacion', $cuv)
            ->with(['sucursal', 'pedido.items'])
            ->firstOrFail();

        return Inertia::render('Public/ComprobanteVerificar', [
            'comprobante' => [
                'id' => $comprobante->id,
                'numero' => $comprobante->numero,
                'codigo_verificacion' => $comprobante->codigo_verificacion,
                'fecha' => $comprobante->created_at->format('d/m/Y H:i'),
                'sucursal' => [
                    'nombre' => $comprobante->sucursal->nombre,
                    'direccion' => $comprobante->sucursal->direccion,
                    'telefono' => $comprobante->sucursal->telefono,
                ],
                'cliente_nombre' => $comprobante->cliente_nombre,
                'cliente_email' => $comprobante->cliente_email,
                'subtotal' => (float) $comprobante->subtotal,
                'descuento' => (float) $comprobante->descuento,
                'impuesto_total' => (float) $comprobante->impuesto_total,
                'propina' => (float) $comprobante->propina,
                'total' => (float) $comprobante->total,
                'metodo_pago' => $comprobante->metodo_pago,
                'hash_pdf' => $comprobante->hash_pdf,
                'estado' => $comprobante->estado->value,
                'items' => $comprobante->pedido->items->map(fn ($item) => [
                    'id' => $item->id,
                    'nombre' => $item->nombre_item,
                    'cantidad' => (float) $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'subtotal' => (float) $item->subtotal,
                    'total' => (float) $item->total,
                ]),
            ],
        ]);
    }

    /**
     * Muestra la tirilla imprimible en formato térmico de 80 mm.
     */
    public function tirilla(string $cuv): HttpResponse
    {
        $comprobante = Comprobante::withoutGlobalScopes()
            ->where('codigo_verificacion', $cuv)
            ->with(['sucursal', 'pedido.items'])
            ->firstOrFail();

        $generador = app(GenerarComprobanteAction::class);
        $ticketTxt = $generador->renderizarTicketHtml($comprobante->pedido, $comprobante->numero, $comprobante->codigo_verificacion);

        return response($ticketTxt, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
