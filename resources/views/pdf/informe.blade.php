<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Operación - {{ $sucursal->nombre }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #292524;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #ea580c;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1c1917;
            margin: 0;
        }
        .subtitle {
            font-size: 11px;
            color: #78716c;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            background: #ffedd5;
            color: #9a3412;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
        }
        .metrics-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .metrics-grid td {
            background: #fafaf9;
            border: 1px solid #e7e5e4;
            border-radius: 6px;
            padding: 10px;
            width: 25%;
            vertical-align: top;
        }
        .metric-label {
            font-size: 9px;
            color: #78716c;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
        }
        .metric-value {
            font-size: 14px;
            font-weight: bold;
            color: #0c0a09;
            margin-top: 3px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1c1917;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 8px;
            border-left: 3px solid #ea580c;
            padding-left: 6px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        table.data-table th {
            background: #f5f5f4;
            color: #44403c;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            border-bottom: 1px solid #d6d3d1;
        }
        table.data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e7e5e4;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e7e5e4;
            padding-top: 8px;
            font-size: 9px;
            color: #a8a29e;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 65%;">
                    <h1 class="title">SIGR — Informe Ejecutivo de Operación</h1>
                    <div class="subtitle">
                        <strong>{{ $sucursal->nombre }}</strong> &bull; {{ $sucursal->direccion }} &bull; Tel: {{ $sucursal->telefono }}
                    </div>
                </td>
                <td style="width: 35%; text-align: right;">
                    <div class="badge">Rango: {{ $periodo_label }}</div>
                    <div style="font-size: 9px; color: #78716c; margin-top: 5px;">
                        Generado: {{ now()->format('d/m/Y H:i:s') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Resumen Métricas -->
    <table class="metrics-grid">
        <tr>
            <td>
                <span class="metric-label">Ventas Totales</span>
                <div class="metric-value" style="color: #ea580c;">${{ number_format($metricas['totalVentas'], 0, ',', '.') }}</div>
            </td>
            <td>
                <span class="metric-label">Subtotal Ventas</span>
                <div class="metric-value">${{ number_format($metricas['totalSubtotal'], 0, ',', '.') }}</div>
            </td>
            <td>
                <span class="metric-label">Impuestos (INC/IVA)</span>
                <div class="metric-value">${{ number_format($metricas['totalImpuestos'], 0, ',', '.') }}</div>
            </td>
            <td>
                <span class="metric-label">Propinas Recaudadas</span>
                <div class="metric-value">${{ number_format($metricas['totalPropinas'], 0, ',', '.') }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <span class="metric-label">Comandas Pagadas</span>
                <div class="metric-value">{{ $metricas['totalPedidos'] }} pedidos</div>
            </td>
            <td>
                <span class="metric-label">Compras a Proveedores</span>
                <div class="metric-value">${{ number_format($metricas['totalCompras'], 0, ',', '.') }}</div>
            </td>
            <td>
                <span class="metric-label">Descuadre en Cajas</span>
                <div class="metric-value" style="color: {{ $metricas['descuadreCajas'] < 0 ? '#dc2626' : '#16a34a' }};">
                    ${{ number_format($metricas['descuadreCajas'], 0, ',', '.') }}
                </div>
            </td>
            <td>
                <span class="metric-label">Ticket Promedio</span>
                <div class="metric-value">
                    ${{ $metricas['totalPedidos'] > 0 ? number_format($metricas['totalVentas'] / $metricas['totalPedidos'], 0, ',', '.') : '0' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Ventas por Modalidad -->
    <div class="section-title">Ventas Consolidadas por Modalidad</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Modalidad</th>
                <th class="text-center">Órdenes</th>
                <th class="text-right">Total Facturado</th>
                <th class="text-right">Participación (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventasPorModalidad as $modalidad)
                <tr>
                    <td><strong>{{ $modalidad['etiqueta'] }}</strong></td>
                    <td class="text-center">{{ $modalidad['cantidad'] }}</td>
                    <td class="text-right">${{ number_format($modalidad['total'], 0, ',', '.') }}</td>
                    <td class="text-right">
                        {{ $metricas['totalVentas'] > 0 ? number_format(($modalidad['total'] / $metricas['totalVentas']) * 100, 1) : 0 }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color: #a8a29e;">No hay ventas registradas en el período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Top Productos Vendidos -->
    <div class="section-title">Top Platos y Bebidas Más Vendidos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="text-center">Unidades</th>
                <th class="text-right">Total Ventas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProductos as $idx => $prod)
                <tr>
                    <td style="width: 30px;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $prod['nombre'] }}</strong></td>
                    <td class="text-center">{{ number_format($prod['cantidad'], 0) }}</td>
                    <td class="text-right">${{ number_format($prod['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color: #a8a29e;">No hay productos vendidos en el período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        SIGR — Sistema Integral de Gestión de Restaurante &bull; Documento generado automáticamente con fines administrativos.
    </div>
</body>
</html>

