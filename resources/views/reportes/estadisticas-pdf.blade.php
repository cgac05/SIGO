<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estadísticas - Certificación Digital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }
        .header {
            border-bottom: 3px solid #1F4E78;
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            color: #1F4E78;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header-info {
            text-align: right;
            font-size: 11px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #1F4E78;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #1F4E78;
            font-size: 12px;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .kpi-box {
            display: inline-block;
            width: 48%;
            margin: 1%;
            padding: 15px;
            background-color: #f0f0f0;
            border-left: 4px solid #1F4E78;
            text-align: center;
        }
        .kpi-label {
            font-weight: bold;
            color: #1F4E78;
            font-size: 12px;
        }
        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #1F4E78;
            margin-top: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #e8f4f8;
            border-left: 4px solid #00a4cc;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>REPORTE DE ESTADÍSTICAS DE CERTIFICACIÓN DIGITAL</h1>
            <div class="header-info">
                <p><strong>Fecha del Reporte:</strong> {{ $fecha_reporte }}</p>
            </div>
        </div>

        <!-- KPIs Generales -->
        <div class="section">
            <div class="section-title">📊 INDICADORES CLAVE (KPIs)</div>
            
            <div class="kpi-box">
                <div class="kpi-label">Total de Desembolsos Certificados</div>
                <div class="kpi-value">{{ $estadisticas['total'] ?? 0 }}</div>
            </div>

            <div class="kpi-box">
                <div class="kpi-label">Certificados Generados</div>
                <div class="kpi-value">{{ $estadisticas['certificados'] ?? 0 }}</div>
            </div>

            <div class="kpi-box">
                <div class="kpi-label">Certificados Validados</div>
                <div class="kpi-value">{{ $estadisticas['validados'] ?? 0 }}</div>
            </div>

            <div class="kpi-box">
                <div class="kpi-label">Tasa de Certificación</div>
                <div class="kpi-value">{{ $estadisticas['porcentaje_certificacion'] ?? 0 }}%</div>
            </div>
        </div>

        <!-- Resumen Financiero -->
        <div class="section">
            <div class="section-title">💰 RESUMEN FINANCIERO</div>
            
            <div class="info-box">
                Monto Total Certificado: <strong>${{ number_format($estadisticas['monto_total_certificado'] ?? 0, 2) }}</strong>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th style="text-align: right;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monto Total Certificado</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($estadisticas['monto_total_certificado'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Promedio por Certificado</td>
                        <td style="text-align: right;">$@php
                            $promedio = ($estadisticas['total'] > 0) 
                                ? $estadisticas['monto_total_certificado'] / $estadisticas['total'] 
                                : 0;
                            echo number_format($promedio, 2);
                        @endphp</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado de Certificados -->
        <div class="section">
            <div class="section-title">📋 ESTADO DE LOS CERTIFICADOS</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Monto Total</th>
                        <th style="text-align: right;">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total_general = 0; @endphp
                    @foreach($ejecucion_por_categoria as $estado => $datos)
                    <tr>
                        <td>
                            @if($estado === 'CERTIFICADO')
                                ✅ Certificado
                            @elseif($estado === 'VALIDADO')
                                🔍 Validado
                            @else
                                {{ $estado }}
                            @endif
                        </td>
                        <td style="text-align: right;">{{ $datos['total'] ?? 0 }}</td>
                        <td style="text-align: right;">${{ number_format($datos['monto'] ?? 0, 2) }}</td>
                        <td style="text-align: right;">
                            @php
                                $porcentaje = ($estadisticas['total'] > 0) 
                                    ? (($datos['total'] ?? 0) / $estadisticas['total']) * 100 
                                    : 0;
                                echo number_format($porcentaje, 1) . '%';
                            @endphp
                        </td>
                    </tr>
                    @php $total_general += $datos['total'] ?? 0; @endphp
                    @endforeach
                    <tr style="background-color: #e8e8e8; font-weight: bold;">
                        <td>TOTAL</td>
                        <td style="text-align: right;">{{ $total_general }}</td>
                        <td style="text-align: right;">$@php
                            $monto_total = collect($ejecucion_por_categoria)->sum('monto');
                            echo number_format($monto_total, 2);
                        @endphp</td>
                        <td style="text-align: right;">100.0%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>
                Reporte generado automáticamente por el Sistema Integrado de Gestión de Orfandad (SIGO)
                <br>
                {{ $fecha_reporte }}
            </p>
        </div>
    </div>
</body>
</html>
