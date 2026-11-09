<?php

namespace App\Http\Controllers\Cliente;

use App\Actions\Comprobantes\GenerarComprobanteAction;
use App\Actions\Comprobantes\ReenviarComprobanteAction;
use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ComprobanteController extends Controller
{
    /**
     * «Mis comprobantes» en el dashboard del cliente (RF-FAC-05).
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $comprobantes = Comprobante::withoutGlobalScopes()
            ->where('cliente_id', $userId)
            ->with(['sucursal'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Comprobante $c) => [
                'id' => $c->id,
                'numero' => $c->numero,
                'codigo_verificacion' => $c->codigo_verificacion,
                'sucursal' => $c->sucursal->nombre,
                'fecha' => $c->created_at->format('d/m/Y H:i'),
                'total' => (float) $c->total,
                'metodo_pago' => $c->metodo_pago,
                'estado' => $c->estado->value,
                'hash_pdf' => $c->hash_pdf,
            ]);

        return Inertia::render('Cliente/Comprobantes/Index', [
            'comprobantes' => $comprobantes,
        ]);
    }

    /**
     * Descarga del comprobante de venta en formato texto / ticket.
     */
    public function descargar(Request $request, Comprobante $comprobante): HttpResponse
    {
        if ((int) $comprobante->cliente_id !== (int) $request->user()->id) {
            abort(403, 'No tienes autorización para acceder a este comprobante.');
        }

        $comprobante->loadMissing(['sucursal', 'pedido.items']);
        $generador = app(GenerarComprobanteAction::class);
        $ticketTxt = $generador->renderizarTicketHtml($comprobante->pedido, $comprobante->numero, $comprobante->codigo_verificacion);

        return response($ticketTxt, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"comprobante-{$comprobante->numero}.txt\"",
        ]);
    }

    /**
     * Reenvío del comprobante a correo personal o designado (CA-31).
     */
    public function reenviar(Request $request, Comprobante $comprobante, ReenviarComprobanteAction $reenviarAction): RedirectResponse
    {
        if ((int) $comprobante->cliente_id !== (int) $request->user()->id) {
            abort(403, 'No tienes autorización para acceder a este comprobante.');
        }

        $validated = $request->validate([
            'correos' => ['required', 'array', 'min:1', 'max:3'],
            'correos.*' => ['required', 'email'],
            'mensaje' => ['nullable', 'string', 'max:300'],
        ]);

        $reenviarAction->handle($comprobante, $validated['correos'], $validated['mensaje'] ?? null, $request->user());

        return back()->with('success', 'Comprobante reenviado exitosamente a los destinatarios especificados.');
    }
}
