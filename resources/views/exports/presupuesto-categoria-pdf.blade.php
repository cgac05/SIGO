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
        .category-header {
            background: linear-gradient(135deg, #1F4E78 0%, #4472C4 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .category-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: bold;
            color: #1F4E78;
            font-size: 14px;
            margin-bottom: 15px;
            border-left: 4px solid #4472C4;
            padding-left: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-card {
            background: #f0f4f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        .info-card .label {
            font-size: 10px;
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #1F4E78;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
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
        .progress-section {
            margin-bottom: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4472C4 0%, #70AD47 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            transition: width 0.3s ease;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table thead {
            background-color: #366092;
            color: white;
        }
        table th,
        table td {
            padding: 10px 12px;
            text-align: left;
            border: 1px solid #ddd;
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
        .summary-box {
            background: #f9f9f9;
            border-left: 4px solid #4472C4;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }
        .summary-box p {
            margin-bottom: 8px;
        }
        .summary-box strong {
            color: #1F4E78;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Generado: {{ $fecha }}</p>
    </div>

    <div class="category-header">
        <h2>{{ $categoria->nombre }}</h2>
        <p>Ciclo Presupuestario: {{ $ciclo->año ?? 'N/A' }} | Estado: {{ $ciclo->estado ?? 'N/A' }}</p>
    </div>

    <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
    <div class="section">
        <div class="section-title">📋 INFORMACIÓN GENERAL</div>
        <div class="info-grid">
            <div class="info-card">
                <div class="label">Presupuesto Anual Asignado</div>
                <div class="value">${{ number_format($categoria->presupuesto_anual, 0) }}</div>
            </div>
            <div class="info-card">
                <div class="label">Presupuesto Utilizado</div>
                <div class="value">${{ number_format($categoria->presupuesto_utilizado, 0) }}</div>
            </div>
            <div class="info-card">
                <div class="label">Presupuesto Disponible</div>
                <div class="value">${{ number_format($disponible, 0) }}</div>
            </div>
            <div class="info-card">
                <div class="label">Estado Actual</div>
                <div>
                    @php
                        $estado_class = 'status-normal';
                        if ($porcentaje >= 95) {
                            $estado_class = 'status-critica';
                        } elseif ($porcentaje >= 85) {
                            $estado_class = 'status-roja';
                        } elseif ($porcentaje >= 70) {
                            $estado_class = 'status-amarilla';
                        }
                    @endphp
                    <span class="status-badge {{ $estado_class }}">{{ $estado['nivel'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: INDICADORES DE UTILIZACIÓN -->
    <div class="section">
        <div class="section-title">📊 INDICADORES DE UTILIZACIÓN</div>
        
        <div class="progress-section">
            <div class="progress-labels">
                <span>Utilización: {{ number_format($porcentaje, 2) }}%</span>
                <span>Disponible: {{ number_format(100 - $porcentaje, 2) }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $porcentaje }}%;">
                    @if($porcentaje > 10)
                        {{ number_format($porcentaje, 1) }}%
                    @endif
                </div>
            </div>
        </div>

        <div class="summary-box">
            <p><strong>Análisis de Utilización:</strong></p>
            @if($porcentaje >= 95)
                <p>🔴 <strong style="color: #C00000;">ESTADO CRÍTICO</strong> - La categoría ha alcanzado el 95% o más de su presupuesto. Se recomienda revisar urgentemente la asignación de nuevos apoyos.</p>
            @elseif($porcentaje >= 85)
                <p>🔴 <strong style="color: #FF0000;">ESTADO ROJO</strong> - La categoría ha alcanzado entre el 85-95% de su presupuesto. Se sugiere evaluar cuidadosamente las nuevas solicitudes.</p>
            @elseif($porcentaje >= 70)
                <p>🟡 <strong style="color: #FFC000;">ESTADO AMARILLO</strong> - La categoría ha utilizado entre el 70-85% de su presupuesto. Existe margen, pero se debe monitorear.</p>
            @else
                <p>🟢 <strong style="color: #00B050;">ESTADO NORMAL</strong> - La categoría tiene disponible más del 30% de su presupuesto. Margen amplio para nuevas asignaciones.</p>
            @endif
        </div>
    </div>

    <!-- SECCIÓN 3: RESUMEN FINANCIERO -->
    <div class="section">
        <div class="section-title">💰 RESUMEN FINANCIERO</div>
        
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right">Monto</th>
                    <th class="text-center">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Presupuesto Total Asignado</strong></td>
                    <td class="text-right"><strong>${{ number_format($categoria->presupuesto_anual, 0) }}</strong></td>
                    <td class="text-center"><strong>100%</strong></td>
                </tr>
                <tr>
                    <td>Presupuesto Utilizado</td>
                    <td class="text-right">${{ number_format($categoria->presupuesto_utilizado, 0) }}</td>
                    <td class="text-center">{{ number_format($porcentaje, 2) }}%</td>
                </tr>
                <tr style="background-color: #f0f4f8;">
                    <td><strong>Presupuesto Disponible</strong></td>
                    <td class="text-right"><strong>${{ number_format($disponible, 0) }}</strong></td>
                    <td class="text-center"><strong>{{ number_format(100 - $porcentaje, 2) }}%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- SECCIÓN 4: OBSERVACIONES -->
    <div class="section">
        <div class="section-title">📝 OBSERVACIONES</div>
        
        <div class="summary-box">
            <p><strong>Descripción:</strong></p>
            <p>{{ $categoria->descripcion ?? 'Sin descripción registrada' }}</p>
            
            @if($categoria->notas)
                <p style="margin-top: 10px;"><strong>Notas Administrativas:</strong></p>
                <p>{{ $categoria->notas }}</p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Este documento es generado automáticamente por SIGO - Sistema Integrado de Gestión de Orfandad</p>
        <p>Instituto Nayarita de la Juventud (INJUVE) - Tecnológico Nacional de México, Campus Tepic</p>
        <p style="margin-top: 10px; font-size: 9px;">Confidencial - Uso Administrativo Interno</p>
    </div>
</body>
</html>
