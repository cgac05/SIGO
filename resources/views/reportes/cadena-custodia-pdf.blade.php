<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cadena de Custodia - {{ $desembolso->fk_folio }}</title>
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
            word-break: break-word;
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
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #1F4E78;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding: 12px;
            background-color: #f9f9f9;
            border-left: 3px solid #1F4E78;
            border-radius: 3px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -11px;
            top: 18px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #1F4E78;
            border: 2px solid white;
        }
        .timeline-type {
            font-weight: bold;
            color: #1F4E78;
            margin-bottom: 3px;
            font-size: 13px;
        }
        .timeline-date {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .timeline-detail {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }
        .hash-box {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 10px;
            word-break: break-all;
            border-left: 3px solid #1F4E78;
        }
        .summary-box {
            background-color: #e8f4f8;
            border-left: 4px solid #00a4cc;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .summary-title {
            color: #00a4cc;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .status-certificado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-validado {
            background-color: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>🔗 CADENA DE CUSTODIA DIGITAL</h1>
            <p class="header-subtitle">Registro de Eventos de Certificación y Validación</p>
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
                    <div class="info-label">Estado de Certificación</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower($desembolso->estado_certificacion) }}">
                            {{ $desembolso->estado_certificacion }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Entrega</div>
                    <div class="info-value">{{ $desembolso->fecha_entrega->format('d/m/Y H:i:s') }}</div>
                </div>
            </div>
        </div>

        <!-- Hash del Certificado -->
        <div class="section">
            <div class="section-title">🔐 HASH DEL CERTIFICADO</div>
            <div class="hash-box">{{ $desembolso->hash_certificado }}</div>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                Este hash SHA-256 identifica de manera única este certificado. Cualquier cambio en los datos 
                generaría un hash diferente, lo que permite verificar la integridad del documento.
            </p>
        </div>

        <!-- Cadena de Custodia -->
        <div class="section">
            <div class="section-title">📋 HISTORIAL DE EVENTOS</div>

            @if(!empty($cadena_custodia))
                <div class="summary-box">
                    <div class="summary-title">✓ Certificado Verificado</div>
                    <p>Este certificado tiene {{ count($cadena_custodia) }} evento(s) registrado(s) en su cadena de custodia.</p>
                </div>

                <div class="timeline">
                    @foreach($cadena_custodia as $evento)
                    <div class="timeline-item">
                        <div class="timeline-type">
                            @if($evento['tipo_evento'] === 'CREACION_CERTIFICADO')
                                🔐 Creación del Certificado
                            @elseif($evento['tipo_evento'] === 'VALIDACION')
                                🔍 Validación de Integridad
                            @else
                                📝 {{ $evento['tipo_evento'] }}
                            @endif
                        </div>

                        <div class="timeline-date">
                            @if(isset($evento['fecha_creacion']))
                                <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($evento['fecha_creacion'])->format('d/m/Y H:i:s') }}
                            @elseif(isset($evento['fecha_validacion']))
                                <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($evento['fecha_validacion'])->format('d/m/Y H:i:s') }}
                            @endif
                        </div>

                        <div class="timeline-detail">
                            <p><strong>IP Terminal:</strong> {{ $evento['ip_terminal'] ?? 'N/A' }}</p>

                            @if(isset($evento['id_usuario_validador']))
                                <p><strong>Usuario Validador:</strong> ID {{ $evento['id_usuario_validador'] }}</p>
                            @endif

                            @if(isset($evento['notas']) && $evento['notas'])
                                <p><strong>Notas:</strong> {{ $evento['notas'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="summary-box">
                    <p>Sin eventos registrados en la cadena de custodia.</p>
                </div>
            @endif
        </div>

        <!-- Información del Beneficiario -->
        <div class="section">
            <div class="section-title">👤 INFORMACIÓN DEL BENEFICIARIO</div>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <div class="info-label">Beneficiario</div>
                        <div class="info-value">{{ $desembolso->solicitud->beneficiario->display_name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <div class="info-label">Programa de Apoyo</div>
                        <div class="info-value">{{ $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificado de Custodia -->
        <div class="summary-box" style="text-align: center; margin-top: 30px;">
            <div class="summary-title">Certificado de Cadena de Custodia</div>
            <p>
                Por medio de este documento se certifica que el desembolso identificado como <strong>{{ $desembolso->fk_folio }}</strong>
                ha sido registrado, certificado y validado de acuerdo con los procedimientos establecidos.
            </p>
            <p style="margin-top: 10px;">
                <strong>Generado:</strong> {{ $fecha_reporte }}<br>
                <strong>Identificador de Certificado:</strong> #{{ $desembolso->id_historico }}
            </p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>
                Documento generado automáticamente por el Sistema Integrado de Gestión de Orfandad (SIGO)
                <br>
                Este documento constituye un registro legal de la cadena de custodia del desembolso
                <br>
                De conformidad con la Ley General de Protección de Datos Personales (LGPDP)
            </p>
        </div>
    </div>
</body>
</html>
