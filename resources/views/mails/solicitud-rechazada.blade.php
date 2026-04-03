<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Rechazada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #7c2d12 0%, #92400e 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 30px;
            background-color: #ffffff;
        }
        .greeting {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .alert-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box .label {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        .alert-box .message {
            color: #b45309;
        }
        .detail-section {
            background-color: #f9fafb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #6b7280;
        }
        .detail-value {
            color: #111827;
        }
        .reason-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-box .label {
            font-weight: bold;
            color: #7f1d1d;
            margin-bottom: 5px;
        }
        .reason-box .content {
            color: #991b1b;
            padding: 0;
        }
        .next-steps {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .next-steps .label {
            font-weight: bold;
            color: #166534;
            margin-bottom: 10px;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
            color: #166534;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .action-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
        }
        .action-button:hover {
            background-color: #1d4ed8;
        }
        .secondary-button {
            display: inline-block;
            background-color: #e5e7eb;
            color: #374151;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 10px 10px 0;
        }
        .secondary-button:hover {
            background-color: #d1d5db;
        }
        .footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .divider {
            border: 0;
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
        .important-notice {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border: 1px solid #fcd34d;
        }
        .important-notice .title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
        }
        .important-notice .text {
            color: #b45309;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>⚠️ Solicitud Rechazada</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <p>Estimad@{{ $beneficiario->genero === 'M' ? 'o' : 'a' }} <strong>{{ $beneficiario->nombre }}</strong>,</p>
            </div>

            <!-- Main Alert -->
            <div class="alert-box">
                <div class="label">Notificación Importante</div>
                <div class="message">
                    Lamentablemente, tu solicitud <strong>#{{ str_pad($solicitud->id, 6, '0', STR_PAD_LEFT) }}</strong> ha sido rechazada en el proceso de evaluación.
                </div>
            </div>

            <!-- Details -->
            <div class="detail-section">
                <div class="detail-row">
                    <span class="detail-label">Folio de solicitud:</span>
                    <span class="detail-value">{{ $solicitud->folio }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value"><strong>Rechazada</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha de notificación:</span>
                    <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Reason -->
            <div class="reason-box">
                <div class="label">Motivo del Rechazo:</div>
                <div class="content" style="margin-top: 10px;">
                    <p style="margin: 0;">{{ $motivo }}</p>
                </div>
            </div>

            <!-- Important Notice -->
            <div class="important-notice">
                <div class="title">📋 Información Adicional</div>
                <div class="text">
                    Este rechazo fue determinado después de un análisis exhaustivo de tu solicitud y documentación presentada. Revisa detalladamente el motivo indicado arriba para entender las razones específicas.
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <div class="label">¿Qué hacer ahora?</div>
                <ul>
                    <li>Revisa el motivo del rechazo cuidadosamente</li>
                    <li>Si tienes preguntas sobre la decisión, contacta al equipo administrativo</li>
                    <li>Puedes presentar una nueva solicitud en futuras convocatorias (verifica fechas de apertura)</li>
                    <li>Guarda una copia del folio de tu solicitud para futuras referencias</li>
                </ul>
            </div>

            <!-- Call to Action -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url("/solicitud/{$solicitud->id}") }}" class="action-button">Ver Detalles Completos</a>
            </div>

            <div style="text-align: center;">
                <p style="margin: 10px 0; font-size: 14px; color: #6b7280;">
                    ¿Necesitas ayuda presencial? Contacta con el equipo de atención al usuario
                </p>
            </div>

            <hr class="divider">

            <!-- Support Info -->
            <div style="background-color: #f3f4f6; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <p style="margin: 0; font-size: 14px;">
                    <strong>📞 Soporte y Atención</strong><br>
                    Si deseas apelar esta decisión o tienes cuestionamientos, puedes contactar al equipo administrativo a través de:
                </p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 14px;">
                    <li>Correo: <strong>soporte@sigo.mx</strong></li>
                    <li>Formulario de contacto en tu cuenta SIGO</li>
                    <li>Línea de atención: Disponible en el portal del INJUVE</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;">
                Este es un correo automático del Sistema Integrado de Gestión de Orfandad (SIGO).<br>
                Por favor, no respondas este correo. Si necesitas asistencia, usa el formulario de contacto en SIGO.
            </p>
            <p style="margin: 10px 0 0 0;">
                &copy; {{ date('Y') }} INJUVE - Ministerio de Desarrollo Social. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
