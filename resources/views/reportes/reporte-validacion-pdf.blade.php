<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Validación - {{ $desembolso->fk_folio }}</title>
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
            border-bottom: 3px solid #1F4E78;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1F4E78;
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
            border-left: 4px solid #1F4E78;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #1F4E78;
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
            background-color: #1F4E78;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .validation-item {
            margin-bottom: 15px;
            padding: 12px;
            border-left: 4px solid #1F4E78;
            background-color: #f9f9f9;
        }
        .validation-title {
            font-weight: bold;
            color: #1F4E78;
            margin-bottom: 5px;
        }
        .validation-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .status-ok {
            background-color: #d4edda;
            color: #155724;
        }
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .validation-message {
            font-size: 12px;
            color: #555;
            line-height: 1.5;
        }
        .validation-details {
            background-color: #f0f0f0;
            padding: 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 10px;
            margin-top: 8px;
            word-break: break-all;
        }
        .result-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }
        .result-valid {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .result-warning {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
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
            <h1>📋 REPORTE DE VALIDACIÓN DIGITAL</h1>
            <p class="header-subtitle">Verificación de integridad y cumplimiento de certificados de desembolso</p>
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
                    <div class="info-label">Fecha de Reporte</div>
                    <div class="info-value">{{ $fecha_reporte }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total de Eventos Auditados</div>
                    <div class="info-value">{{ $total_auditorias }}</div>
                </div>
            </div>
        </div>

        <!-- Resultado General -->
        <div class="section">
            <div class="section-title">✓ RESULTADO GENERAL DE VALIDACIÓN</div>
            <div class="result-box @if($validacion['resultado_general']) result-valid @else result-warning @endif">
                @if($validacion['resultado_general'])
                    ✓ CERTIFICADO VÁLIDO EN TODOS LOS CRITERIOS
                @else
                    ⚠ CERTIFICADO CON ALERTAS - REVISAR DETALLES
                @endif
            </div>
        </div>

        <!-- Validaciones Detalladas -->
        <div class="section">
            <div class="section-title">📊 VALIDACIONES DETALLADAS</div>

            @foreach($validacion['validaciones'] as $nombre => $validacion_item)
            <div class="validation-item">
                <div class="validation-title">{{ ucfirst(str_replace('_', ' ', $nombre)) }}</div>
                <div>
                    <span class="validation-status @if($validacion_item['valido']) status-ok @else status-warning @endif">
                        @if($validacion_item['valido']) ✓ VÁLIDO @else ⚠ ALERTA @endif
                    </span>
                </div>
                <div class="validation-message">{{ $validacion_item['mensaje'] }}</div>

                @if($nombre === 'integridad')
                <div class="validation-details">
                    Hash Almacenado: {{ $validacion_item['hash_servidor'] }}<br>
                    Hash Verificado: {{ $validacion_item['hash_verificado'] }}
                </div>
                @elseif($nombre === 'montos')
                <div class="validation-details">
                    Monto: ${{ number_format($validacion_item['monto'], 2) }}
                </div>
                @elseif($nombre === 'fechas')
                <div class="validation-details">
                    Entrega: {{ $validacion_item['fecha_entrega'] }}<br>
                    Certificación: {{ $validacion_item['fecha_certificacion'] }}
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Información de Auditoría -->
        @if($auditoria['auditorias'] && $auditoria['auditorias']->count() > 0)
        <div class="section">
            <div class="section-title">🔗 RESUMEN DE CADENA DE CUSTODIA</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">Certificado Creado</div>
                        <div class="info-value">{{ $auditoria['resumen']['certificado_creado'] }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total de Eventos</div>
                        <div class="info-value">{{ $auditoria['resumen']['total_eventos'] }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Última Verificación</div>
                        <div class="info-value">{{ $auditoria['resumen']['ultima_verificacion'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Verificaciones de Integridad</div>
                        <div class="info-value">{{ $auditoria['resumen']['verificaciones_integridad'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Pie de página -->
        <div class="footer">
            <p>
                Documento generado automáticamente por el Sistema Integrado de Gestión de Orfandad (SIGO)
                <br>
                Este reporte constituye un registro legal de la validación realizada
                <br>
                De conformidad con la Ley General de Protección de Datos Personales (LGPDP)
            </p>
        </div>
    </div>
</body>
</html>
