<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Compras, Devoluciones y Anulaciones — SIGR</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1917; margin: 20px; }
        h1 { font-size: 16px; margin-bottom: 2px; color: #ea580c; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #d6d3d1; padding-bottom: 3px; color: #292524; }
        .meta { font-size: 10px; color: #57534e; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #e7e5e4; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background-color: #f5f5f4; font-size: 9px; text-transform: uppercase; color: #44403c; }
        .right { text-align: right; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .subdetalle { font-size: 9.5px; color: #57534e; margin-top: 3px; }
    </style>
</head>
<body>
    <h1>SIGR — Informe de Compras, Devoluciones y Anulaciones</h1>
    <div class="meta">
        <strong>Sucursal:</strong> {{ $sucursal?->nombre ?? 'General' }} |
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta']))
            | <strong>Rango:</strong> {{ $filtros['fecha_desde'] ?? 'Inicio' }} a {{ $filtros['fecha_hasta'] ?? 'Hoy' }}
        @endif
        @if(!empty($filtros['buscar']))
            | <strong>Búsqueda:</strong> "{{ $filtros['buscar'] }}"
        @endif
    </div>

    <h2>1. Registro de Compras ({{ count($compras) }})</h2>
    <table>
        <thead>
            <tr>
                <th>Factura #</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Detalle de Ítems / Devoluciones / Anulación</th>
                <th class="right">Subtotal</th>
                <th class="right">Impuesto</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($compras as $c)
                <tr>
                    <td><strong>{{ $c->numero_factura }}</strong></td>
                    <td>{{ $c->proveedor?->razon_social }}<br><span class="subdetalle">{{ $c->proveedor?->nit }}</span></td>
                    <td>{{ $c->fecha_compra?->format('d/m/Y') }}</td>
                    <td><span class="badge">{{ $c->estado->etiqueta() }}</span></td>
                    <td>
                        @foreach($c->items as $it)
                            <div>• {{ $it->descripcion }} ({{ (float) $it->cantidad }} x ${{ number_format((float) $it->costo_unitario, 0) }} = ${{ number_format((float) $it->total, 0) }})</div>
                        @endforeach
                        @if($c->motivo_anulacion)
                            <div class="subdetalle"><strong>Anulación:</strong> {{ $c->motivo_anulacion }} ({{ $c->anuladoPor?->nombre ?? 'Sistema' }})</div>
                        @endif
                        @foreach($c->devoluciones as $dev)
                            <div class="subdetalle"><strong>Devolución ({{ strtoupper($dev->tipo) }}):</strong> ${{ number_format((float) $dev->total_devuelto, 0) }} — {{ $dev->motivo }}</div>
                        @endforeach
                    </td>
                    <td class="right">${{ number_format((float) $c->subtotal, 0) }}</td>
                    <td class="right">${{ number_format((float) $c->impuesto_total, 0) }}</td>
                    <td class="right"><strong>${{ number_format((float) $c->total, 0) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">No hay compras registradas con los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Devoluciones Asociadas a Compras ({{ count($devoluciones) }})</h2>
    <table>
        <thead>
            <tr>
                <th>Factura Compra</th>
                <th>Proveedor</th>
                <th>Fecha Devolución</th>
                <th>Modalidad</th>
                <th>Motivo y Productos Devueltos</th>
                <th>Registrado Por</th>
                <th class="right">Total Devuelto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devoluciones as $d)
                <tr>
                    <td><strong>{{ $d->compra?->numero_factura }}</strong></td>
                    <td>{{ $d->compra?->proveedor?->razon_social }}</td>
                    <td>{{ $d->fecha_devolucion?->format('d/m/Y H:i') }}</td>
                    <td>{{ strtoupper($d->tipo) }}</td>
                    <td>
                        <div><strong>Motivo:</strong> {{ $d->motivo }}</div>
                        @if(is_array($d->items_devueltos))
                            @foreach($d->items_devueltos as $idv)
                                <div class="subdetalle">• {{ $idv['descripcion'] ?? 'Ítem' }}: {{ $idv['cantidad_devuelta'] ?? 0 }} unid. (${{ number_format((float) ($idv['subtotal_devuelto'] ?? 0), 0) }})</div>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $d->user?->nombre ?? 'Sistema' }}</td>
                    <td class="right"><strong>${{ number_format((float) $d->total_devuelto, 0) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No hay devoluciones registradas en el periodo consultado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Compras Anuladas ({{ count($anulaciones) }})</h2>
    <table>
        <thead>
            <tr>
                <th>Factura #</th>
                <th>Proveedor</th>
                <th>Fecha Compra</th>
                <th>Fecha Anulación</th>
                <th>Motivo de Anulación</th>
                <th>Anulado Por</th>
                <th class="right">Monto Revertido</th>
            </tr>
        </thead>
        <tbody>
            @forelse($anulaciones as $an)
                <tr>
                    <td><strong>{{ $an->numero_factura }}</strong></td>
                    <td>{{ $an->proveedor?->razon_social }}</td>
                    <td>{{ $an->fecha_compra?->format('d/m/Y') }}</td>
                    <td>{{ $an->anulado_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $an->motivo_anulacion }}</td>
                    <td>{{ $an->anuladoPor?->nombre ?? 'Sistema' }}</td>
                    <td class="right"><strong>${{ number_format((float) $an->total, 0) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No hay compras anuladas en el periodo consultado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

