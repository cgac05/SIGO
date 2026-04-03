<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #1F4E78;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #1F4E78;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        .kpi-section {
            margin-bottom: 30px;
        }
        .kpi-title {
            font-weight: bold;
            color: #1F4E78;
            font-size: 14px;
            margin-bottom: 10px;
            border-left: 4px solid #4472C4;
            padding-left: 10px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background: #f0f4f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            text-align: center;
        }
        .kpi-card .label {
            font-size: 10px;
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kpi-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #1F4E78;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table thead {
            background-color: #1F4E78;
            color: white;
        }
        table th,
        table td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr:hover {
            background-color: #f0f4f8;
        }
        .text-right {
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .status-normal {
            background-color: #00B050;
            color: white;
        }
        .status-amarilla {
            background-color: #FFC000;
            color: #333;
        }
        .status-roja {
            background-color: #FF0000;
            color: white;
        }
        .status-critica {
            background-color: #C00000;
            color: white;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4472C4 0%, #70AD47 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 9px;
            font-weight: bold;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        @page {
            margin: 20mm;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Reporte generado: {{ $fecha }}</p>
    </div>

    <div class="kpi-section">
        <div class="kpi-title">📊 RESUMEN DE KPI'S</div>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="label">Ciclo</div>
                <div class="value">{{ $ciclo->año }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Estado</div>
                <div class="value">{{ $ciclo->estado }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Presupuesto Total</div>
                <div class="value">${{ number_format($totalPresupuesto, 0) }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Categorías</div>
                <div class="value">{{ $categorias->count() }}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <div class="kpi-title">📈 RESUMEN DE UTILIZACIÓN</div>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="label">Presupuesto Utilizado</div>
                <div class="value">${{ number_format($totalUtilizado, 0) }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Presupuesto Disponible</div>
                <div class="value">${{ number_format($totalDisponible, 0) }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Porcentaje Disponible</div>
                <div class="value">{{ number_format($porcentajeDisponible, 2) }}%</div>
            </div>
            <div class="kpi-card">
                <div class="label">Porcentaje Utilizado</div>
                <div class="value">{{ number_format(100 - $porcentajeDisponible, 2) }}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <div class="kpi-title">📋 DETALLE DE CATEGORÍAS PRESUPUESTARIAS</div>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-right">Presupuesto Anual</th>
                    <th class="text-right">Utilizado</th>
                    <th class="text-right">Disponible</th>
                    <th class="text-right">% Utilizado</th>
                    <th style="text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorias as $cat)
                    @php
                        $porcentaje = $cat->presupuesto_anual > 0 ? ($cat->presupuesto_utilizado / $cat->presupuesto_anual) * 100 : 0;
                        if ($porcentaje >= 95) {
                            $estado = 'critica';
                            $label = 'CRÍTICA';
                        } elseif ($porcentaje >= 85) {
                            $estado = 'roja';
                            $label = 'ROJA';
                        } elseif ($porcentaje >= 70) {
                            $estado = 'amarilla';
                            $label = 'AMARILLA';
                        } else {
                            $estado = 'normal';
                            $label = 'NORMAL';
                        }
                    @endphp
                    <tr>
                        <td><strong>{{ $cat->nombre }}</strong></td>
                        <td class="text-right">${{ number_format($cat->presupuesto_anual, 0) }}</td>
                        <td class="text-right">${{ number_format($cat->presupuesto_utilizado, 0) }}</td>
                        <td class="text-right">${{ number_format($cat->presupuesto_anual - $cat->presupuesto_utilizado, 0) }}</td>
                        <td class="text-right">{{ number_format($porcentaje, 2) }}%</td>
                        <td style="text-align: center;">
                            <span class="status-badge status-{{ $estado }}">{{ $label }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999;">
                            No hay categorías presupuestarias registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Este documento es generado automáticamente por SIGO</p>
        <p>Para más información, contacta al administrador del sistema</p>
    </div>
</body>
</html>
