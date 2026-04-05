<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado Digital - {{ $folio }}</title>
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
            max-width: 800px;
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
            font-size: 28px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 12px;
            letter-spacing: 2px;
        }
        .seal {
            position: absolute;
            right: 30px;
            top: 30px;
            font-size: 60px;
            opacity: 0.3;
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
        .content {
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .row {
            display: flex;
            gap: 30px;
            margin-bottom: 10px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            color: #1F4E78;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .value {
            color: #333;
            font-size: 14px;
            margin-top: 3px;
            word-break: break-word;
        }
        .qrcode {
            text-align: center;
            padding: 20px;
            border: 2px dashed #1F4E78;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .qrcode-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        .qrcode img {
            max-width: 150px;
            height: auto;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .verification-box {
            background-color: #e8f4f8;
            border-left: 4px solid #00a4cc;
            padding: 15px;
            margin-top: 20px;
            font-size: 11px;
        }
        .verification-title {
            color: #00a4cc;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .status-certificado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-validado {
            background-color: #cfe2ff;
            color: #084298;
        }
        .divider {
            height: 1px;
            background-color: #ddd;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="seal">🔐</div>
    
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>CERTIFICADO DIGITAL DE ENTREGA</h1>
            <p class="subtitle">COMPROBANTE DE DESEMBOLSO OFICIAL</p>
        </div>

        <!-- Información Principal -->
        <div class="section">
            <div class="section-title">📋 INFORMACIÓN DEL DESEMBOLSO</div>
            <div class="content">
                <div class="row">
                    <div class="col">
                        <div class="label">Folio del Desembolso</div>
                        <div class="value">{{ $folio }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Monto Entregado</div>
                        <div class="value"><strong>${{ $monto }}</strong></div>
                    </div>
                    <div class="col">
                        <div class="label">Estado</div>
                        <div class="value">
                            <span class="status-badge status-{{ strtolower($estado) }}">{{ $estado }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Fechas -->
        <div class="section">
            <div class="section-title">📅 INFORMACIÓN DE FECHAS</div>
            <div class="content">
                <div class="row">
                    <div class="col">
                        <div class="label">Fecha de Entrega</div>
                        <div class="value">{{ $fecha_entrega }} a las {{ $hora_entrega }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Fecha de Certificación</div>
                        <div class="value">{{ $fecha_certificacion }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Beneficiario -->
        <div class="section">
            <div class="section-title">👤 INFORMACIÓN DEL BENEFICIARIO</div>
            <div class="content">
                <div class="row">
                    <div class="col">
                        <div class="label">Beneficiario</div>
                        <div class="value">{{ $beneficiario }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Programa de Apoyo</div>
                        <div class="value">{{ $programa }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Técnica del Certificado -->
        <div class="section">
            <div class="section-title">🔐 DATOS DEL CERTIFICADO DIGITAL</div>
            <div class="content">
                <div>
                    <div class="label">Hash SHA-256</div>
                    <div class="value" style="font-family: monospace; background-color: #f0f0f0; padding: 10px; border-radius: 3px; word-break: break-all; font-size: 10px;">
                        {{ $hash_certificado }}
                    </div>
                </div>

                <div class="divider"></div>

                @if($ruta_qrcode)
                <div class="qrcode">
                    <p class="qrcode-label">Código QR de Verificación</p>
                    <img src="{{ public_path('storage/' . $ruta_qrcode) }}" alt="QR Code">
                    <p style="margin-top: 10px; font-size: 10px; color: #666;">
                        Escanea con aplicación de códigos QR para verificar integridad
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- Información de Registrador -->
        <div class="section">
            <div class="section-title">👨‍💼 INFORMACIÓN DE PROCESAMIENTO</div>
            <div class="content">
                <div class="row">
                    <div class="col">
                        <div class="label">Usuario Registrador</div>
                        <div class="value">{{ $usuario_registrador }}</div>
                    </div>
                    <div class="col">
                        <div class="label">IP Terminal</div>
                        <div class="value">{{ $ip_terminal }}</div>
                    </div>
                    <div class="col">
                        <div class="label">Eventos en Cadena</div>
                        <div class="value">{{ $cadena_custodia_count }} evento(s)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Caja de Verificación -->
        <div class="verification-box">
            <p class="verification-title">✓ VERIFICACIÓN Y VALIDEZ</p>
            <p>
                Este es un certificado digitaloficial que demuestra la entrega de fondos al beneficiario indicado.
                El hash SHA-256 permite verificar que el documento no ha sido modificado.
                Este certificado es válido como comprobante ante autoridades.
            </p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>
                Certificado generado automáticamente por el Sistema Integrado de Gestión de Orfandad (SIGO)
                <br>
                Válido de acuerdo a la Ley General de Protección de Datos Personales (LGPDP)
                <br>
                <br>
                <strong>ID Certificado:</strong> #{{ $id_historico }} | <strong>Generado:</strong> {{ date('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>
