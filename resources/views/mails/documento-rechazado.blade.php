<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Rechazado</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
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
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box .label {
            font-weight: bold;
            color: #7f1d1d;
            margin-bottom: 5px;
        }
        .alert-box .message {
            color: #991b1b;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>❌ Documento Rechazado</h1>
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
                    El documento <strong>"{{ $nombreDocumento }}"</strong> ha sido rechazado durante el proceso de verificación.
                </div>
            </div>

            <!-- Details -->
            <div class="detail-section">
                <div class="detail-row">
                    <span class="detail-label">Documento rechazado:</span>
                    <span class="detail-value">{{ $nombreDocumento }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motivo del rechazo:</span>
                    <span class="detail-value">{{ $motivo }}</span>
                </div>
                @if($idSolicitud)
                <div class="detail-row">
                    <span class="detail-label">Folio de solicitud:</span>
                    <span class="detail-value">#{{ str_pad($idSolicitud, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Fecha de notificación:</span>
                    <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <div class="label">¿Qué hacer ahora?</div>
                <ul>
                    <li>Revisa el motivo del rechazo indicado arriba</li>
                    <li>Prepara una nueva versión del documento corrigiendo los errores señalados</li>
                    <li>Carga el documento actualizado en tu solicitud</li>
                    <li>El equipo de verificación revisará tu documento nuevamente</li>
                </ul>
            </div>

            <!-- Call to Action -->
            <div style="text-align: center;">
                @if($idSolicitud)
                <a href="{{ url("/solicitud/{$idSolicitud}") }}" class="action-button">Ver mi Solicitud</a>
                @endif
            </div>

            <hr class="divider">

            <!-- Support Info -->
            <div style="background-color: #f3f4f6; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <p style="margin: 0; font-size: 14px;">
                    <strong>¿Necesitas ayuda?</strong><br>
                    Si tienes preguntas sobre el motivo del rechazo, contacta al equipo administrativo a través de tu cuenta SIGO o envía un correo a <strong>soporte@sigo.mx</strong>
                </p>
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
