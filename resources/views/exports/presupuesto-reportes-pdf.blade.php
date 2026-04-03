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
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #1F4E78;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1F4E78;
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 10px;
        }
        .section-title {
            font-weight: bold;
            color: #1F4E78;
            font-size: 13px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #4472C4;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        table thead {
            background-color: #1F4E78;
            color: white;
        }
        table th,
        table td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
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
        .alert-summary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .alert-card {
            background: #f0f4f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
        }
        .alert-card .count {
            font-size: 16px;
            font-weight: bold;
            color: #1F4E78;
        }
        .alert-card .label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
        .page-break {
            page-break-after: always;
        }
        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>{{ $nombreMes }} de {{ $año }} | Generado: {{ $fecha }}</p>
    </div>

    <!-- SECCIÓN 1: RESUMEN MENSUAL -->
    <div class="section-title">📋 RESUMEN MENSUAL - {{ strtoupper($nombreMes) }} {{ $año }}</div>
    
    @if($reporteMensual && isset($reporteMensual['categorias']))
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-right">Presupuesto Anual</th>
                    <th class="text-right">Utilizado</th>
                    <th class="text-right">Movimientos</th>
                    <th class="text-right">% Utilizado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporteMensual['categorias'] as $cat)
                    @php
                        $porcentaje = ($cat['presupuesto_anual'] ?? 0) > 0 ? (($cat['presupuesto_utilizado'] ?? 0) / ($cat['presupuesto_anual'] ?? 0)) * 100 : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $cat['nombre'] ?? 'N/A' }}</strong></td>
                        <td class="text-right">${{ number_format($cat['presupuesto_anual'] ?? 0, 0) }}</td>
                        <td class="text-right">${{ number_format($cat['presupuesto_utilizado'] ?? 0, 0) }}</td>
                        <td class="text-right">{{ $cat['movimientos_mes'] ?? 0 }}</td>
                        <td class="text-right"><strong>{{ number_format($porcentaje, 2) }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999; text-align: center;">No hay datos disponibles para este período</p>
    @endif

    <div class="page-break"></div>

    <!-- SECCIÓN 2: ALERTAS PRESUPUESTARIAS -->
    <div class="section-title">⚠️ ALERTAS PRESUPUESTARIAS</div>

    @if($alertas && isset($alertas['contadores']))
        <div class="alert-summary">
            <div class="alert-card">
                <div class="count">{{ $alertas['contadores']['total'] ?? 0 }}</div>
                <div class="label">Total</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #C00000;">{{ $alertas['contadores']['critica'] ?? 0 }}</div>
                <div class="label">Críticas</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #FF0000;">{{ $alertas['contadores']['roja'] ?? 0 }}</div>
                <div class="label">Rojas</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #FFC000;">{{ $alertas['contadores']['amarilla'] ?? 0 }}</div>
                <div class="label">Amarillas</div>
            </div>
        </div>

        @if($alertas['detalle'] && count($alertas['detalle']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-right">% Utilizado</th>
                        <th class="text-center">Nivel</th>
                        <th class="text-right">Disponible</th>
                        <th class="text-right">Utilizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alertas['detalle'] as $alerta)
                        @php
                            if ($alerta['nivel'] == 'CRITICA') $estado = 'critica';
                            elseif ($alerta['nivel'] == 'ROJA') $estado = 'roja';
                            elseif ($alerta['nivel'] == 'AMARILLA') $estado = 'amarilla';
                            else $estado = 'normal';
                        @endphp
                        <tr>
                            <td><strong>{{ $alerta['categoria'] ?? 'N/A' }}</strong></td>
                            <td class="text-right">{{ number_format($alerta['porcentaje'] ?? 0, 2) }}%</td>
                            <td class="text-center">
                                <span class="status-badge status-{{ $estado }}">{{ $alerta['nivel'] ?? 'N/A' }}</span>
                            </td>
                            <td class="text-right">${{ number_format($alerta['disponible'] ?? 0, 0) }}</td>
                            <td class="text-right">${{ number_format($alerta['utilizado'] ?? 0, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #999; text-align: center; padding: 10px;">No hay alertas presupuestarias en este período</p>
        @endif
    @endif

    <div class="page-break"></div>

    <!-- SECCIÓN 3: ESTADÍSTICAS DE APOYOS -->
    <div class="section-title">📊 ESTADÍSTICAS DE APOYOS</div>

    @if($estadisticas && isset($estadisticas['totales']))
        <div class="alert-summary">
            <div class="alert-card">
                <div class="count">{{ $estadisticas['totales']['total'] ?? 0 }}</div>
                <div class="label">Total Apoyos</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #00B050;">{{ $estadisticas['totales']['aprobados'] ?? 0 }}</div>
                <div class="label">Aprobados</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #FFC000;">{{ $estadisticas['totales']['pendientes'] ?? 0 }}</div>
                <div class="label">Pendientes</div>
            </div>
            <div class="alert-card">
                <div class="count" style="color: #FF0000;">{{ $estadisticas['totales']['rechazados'] ?? 0 }}</div>
                <div class="label">Rechazados</div>
            </div>
        </div>

        <p style="text-align: center; margin-top: 10px; font-size: 12px;">
            <strong>Porcentaje de Ejecución: {{ number_format($estadisticas['totales']['porcentaje_ejecucion'] ?? 0, 2) }}%</strong>
        </p>

        @if(isset($estadisticas['detalles']) && count($estadisticas['detalles']) > 0)
            <table style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-right">Total Apoyos</th>
                        <th class="text-right">Aprobados</th>
                        <th class="text-right">Pendientes</th>
                        <th class="text-right">Rechazados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estadisticas['detalles'] as $detalle)
                        <tr>
                            <td><strong>{{ $detalle['categoria'] ?? 'N/A' }}</strong></td>
                            <td class="text-right">{{ $detalle['total'] ?? 0 }}</td>
                            <td class="text-right">{{ $detalle['aprobados'] ?? 0 }}</td>
                            <td class="text-right">{{ $detalle['pendientes'] ?? 0 }}</td>
                            <td class="text-right">{{ $detalle['rechazados'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="footer">
        <p>Este documento es generado automáticamente por SIGO - Sistema Integrado de Gestión de Orfandad</p>
        <p>Instituto Nayarita de la Juventud (INJUVE)</p>
    </div>
</body>
</html>
