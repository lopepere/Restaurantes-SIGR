<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comprobantes\ReenviarComprobanteAction;
use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\EnvioComprobante;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComprobanteController extends Controller
{
    /**
     * Listado administrativo de comprobantes de venta FAC con filtros y auditoría de envíos (RF-FAC-01..06, CA-31).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $esSuperAdmin = $user->hasRole('super_admin');

        $sucursales = $esSuperAdmin
            ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre'])
            : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre']);

        $primeraSucursal = $sucursales->first();
        $primerId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', $primerId));

        if (! $esSuperAdmin && ! in_array($sucursalId, $user->sucursalIds(), true)) {
            abort(403, 'No tienes autorización para acceder a los comprobantes de esta sucursal.');
        }

        $query = Comprobante::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->with(['pedido', 'envios.usuario']);

        if ($filtroNumero = $request->input('numero')) {
            $query->where('numero', 'like', "%{$filtroNumero}%");
        }

        if ($filtroCliente = $request->input('cliente')) {
            $query->where(function ($q) use ($filtroCliente) {
                $q->where('cliente_nombre', 'like', "%{$filtroCliente}%")
                    ->orWhere('cliente_email', 'like', "%{$filtroCliente}%");
            });
        }

        if ($filtroFecha = $request->input('fecha')) {
            $query->whereDate('created_at', $filtroFecha);
        }

        $comprobantes = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Comprobante $c) {
                $ultimoEnvio = $c->envios->first();

                return [
                    'id' => $c->id,
                    'numero' => $c->numero,
                    'codigo_verificacion' => $c->codigo_verificacion,
                    'pedido_numero' => $c->pedido->numero,
                    'fecha' => $c->created_at->format('d/m/Y H:i'),
                    'cliente' => $c->cliente_nombre,
                    'email' => $c->cliente_email,
                    'subtotal' => (float) $c->subtotal,
                    'impuesto' => (float) $c->impuesto_total,
                    'propina' => (float) $c->propina,
                    'total' => (float) $c->total,
                    'metodo_pago' => $c->metodo_pago,
                    'hash_pdf' => $c->hash_pdf,
                    'estado' => $c->estado->value,
                    'envios_count' => $c->envios->count(),
                    'ultimo_envio' => $ultimoEnvio instanceof EnvioComprobante ? [
                        'tipo' => $ultimoEnvio->tipo->etiqueta(),
                        'estado' => $ultimoEnvio->estado->value,
                        'fecha' => $ultimoEnvio->created_at->format('d/m H:i'),
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Comprobantes/Index', [
            'comprobantes' => $comprobantes,
            'filtros' => $request->only(['sucursal_id', 'numero', 'cliente', 'fecha']),
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Reenvío del comprobante de venta a correos designados con mensaje opcional (CA-31).
     */
    public function reenviar(Request $request, Comprobante $comprobante, ReenviarComprobanteAction $reenviarAction): RedirectResponse
    {
        $validated = $request->validate([
            'correos' => ['required', 'array', 'min:1', 'max:3'],
            'correos.*' => ['required', 'email'],
            'mensaje' => ['nullable', 'string', 'max:300'],
        ]);

        $reenviarAction->handle($comprobante, $validated['correos'], $validated['mensaje'] ?? null, $request->user());

        return back()->with('success', "Comprobante {$comprobante->numero} reenviado exitosamente.");
    }
}
