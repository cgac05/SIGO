<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cumplimiento LGPDP - {{ $desembolso->fk_folio }}</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }
        .header {
            border-bottom: 3px solid #006B3F;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #006B3F;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header-subtitle {
            color: #666;
            font-size: 12px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #006B3F;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #006B3F;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            color: #333;
            font-size: 13px;
            margin-top: 3px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #006B3F;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .score-box {
            text-align: center;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 2px solid #006B3F;
            background-color: #f0fdf4;
        }
        .score-value {
            font-size: 48px;
            font-weight: bold;
            color: #006B3F;
            margin-bottom: 5px;
        }
        .score-label {
            font-size: 14px;
            color: #006B3F;
            font-weight: bold;
        }
        .criteria-item {
            margin-bottom: 15px;
            padding: 12px;
            border-left: 4px solid #006B3F;
            background-color: #f9f9f9;
        }
        .criteria-title {
            font-weight: bold;
            color: #006B3F;
            margin-bottom: 5px;
        }
        .criteria-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .status-cumple {
            background-color: #d4edda;
            color: #155724;
        }
        .status-no-cumple {
            background-color: #f8d7da;
            color: #721c24;
        }
        .criteria-description {
            font-size: 12px;
            color: #555;
            margin-bottom: 5px;
        }
        .criteria-evidence {
            background-color: #f0f0f0;
            padding: 8px;
            border-radius: 3px;
            font-size: 11px;
            font-style: italic;
            color: #666;
        }
        .level-box {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        .level-excelente {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        .level-bueno {
            background-color: #cfe2ff;
            color: #084298;
            border: 2px solid #0d6efd;
        }
        .level-aceptable {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        .level-mejorable {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>📋 REPORTE DE CUMPLIMIENTO LGPDP</h1>
            <p class="header-subtitle">Evaluación de Conformidad con Ley General de Protección de Datos Personales</p>
        </div>

        <!-- Información del Certificado -->
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <div class="info-label">Folio del Desembolso</div>
                    <div class="info-value">{{ $desembolso->fk_folio }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Monto Entregado</div>
                    <div class="info-value"><strong>${{ number_format($desembolso->monto_entregado, 2) }}</strong></div>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <div class="info-label">Fecha de Evaluación</div>
                    <div class="info-value">{{ $fecha_reporte }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Programa de Apoyo</div>
                    <div class="info-value">{{ $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Puntuación General -->
        <div class="section">
            <div class="section-title">🏆 PUNTUACIÓN DE CUMPLIMIENTO</div>
            <div class="score-box">
                <div class="score-value">{{ $cumplimiento['cumplimiento_score'] }}/100</div>
                <div class="score-label">
                    <span class="level-box @php
                        if ($cumplimiento['cumplimiento_score'] >= 95) echo 'level-excelente';
                        elseif ($cumplimiento['cumplimiento_score'] >= 75) echo 'level-bueno';
                        elseif ($cumplimiento['cumplimiento_score'] >= 50) echo 'level-aceptable';
                        else echo 'level-mejorable';
                    @endphp">
                        {{ $cumplimiento['nivel_cumplimiento'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Criterios Evaluados -->
        <div class="section">
            <div class="section-title">📊 CRITERIOS DE CUMPLIMIENTO LGPDP</div>

            @foreach($cumplimiento['detalles'] as $criterio_key => $criterio)
            <div class="criteria-item">
                <div class="criteria-title">{{ $criterio['criterio'] }}</div>
                <div>
                    <span class="criteria-status @if($criterio['cumple']) status-cumple @else status-no-cumple @endif">
                        @if($criterio['cumple']) ✓ CUMPLE @else ✗ NO CUMPLE @endif
                    </span>
                </div>
                <div class="criteria-description">{{ $criterio['descripcion'] }}</div>
                <div class="criteria-evidence">
                    <strong>Evidencia:</strong> {{ $criterio['evidencia'] }}
                </div>
                <div style="margin-top: 8px; font-size: 12px; color: #006B3F; font-weight: bold;">
                    Puntuación: {{ $criterio['puntuacion'] }}/25
                </div>
            </div>
            @endforeach
        </div>

        <!-- Resumen de Cumplimiento -->
        <div class="section">
            <div class="section-title">📈 RESUMEN GENERAL</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">Total de Criterios</div>
                        <div class="info-value">{{ $cumplimiento['resumen']['total_criterios'] }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Criterios Cumplidos</div>
                        <div class="info-value" style="color: #006B3F; font-weight: bold;">
                            {{ $cumplimiento['resumen']['criterios_cumplidos'] }}/{{ $cumplimiento['resumen']['total_criterios'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recomendaciones -->
        <div class="section">
            <div class="section-title">💡 RECOMENDACIONES</div>
            <div style="background-color: #f0fdf4; border-left: 4px solid #006B3F; padding: 12px; font-size: 12px; line-height: 1.6;">
                @if($cumplimiento['cumplimiento_score'] >= 95)
                    <p>✓ Este certificado cumple completamente con los requisitos LGPDP. Se recomienda mantener los procedimientos actuales.</p>
                @elseif($cumplimiento['cumplimiento_score'] >= 75)
                    <p>✓ Este certificado cumple adecuadamente con LGPDP. Se sugiere revisar periódicamente para mejora continua.</p>
                @elseif($cumplimiento['cumplimiento_score'] >= 50)
                    <p>⚠ Este certificado requiere mejoras en ciertos criterios. Se recomienda una auditoría complementaria.</p>
                @else
                    <p>✗ Este certificado necesita mejoras significativas en cumplimiento LGPDP. Se requiere acción correctiva.</p>
                @endif
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>
                Reporte generado automáticamente por el Sistema Integrado de Gestión de Orfandad (SIGO)
                <br>
                De conformidad con la Ley General de Protección de Datos Personales (LGPDP)
                <br>
                Fecha de evaluación: {{ $fecha_reporte }}
            </p>
        </div>
    </div>
</body>
</html>
