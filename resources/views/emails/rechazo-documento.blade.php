<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Rechazado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9fafb;
            color: #111827;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            margin-bottom: 24px;
            font-size: 16px;
        }

        .greeting strong {
            color: #1f2937;
        }

        .alert-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .alert-box .label {
            font-weight: bold;
            color: #7f1d1d;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .alert-box .message {
            color: #b91c1c;
            font-size: 15px;
        }

        .info-table {
            width: 100%;
            margin: 24px 0;
            border-collapse: collapse;
        }

        .info-table tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .info-table tr:last-child {
            border-bottom: none;
        }

        .info-table td {
            padding: 12px 0;
            font-size: 14px;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
        }

        .info-table td:last-child {
            color: #111827;
            word-break: break-word;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 24px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }

        .reason-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 16px 0;
            border-radius: 6px;
        }

        .reason-box .label {
            font-weight: bold;
            color: #78350f;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .reason-box .text {
            color: #92400e;
            font-size: 14px;
            line-height: 1.6;
        }

        .action-box {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .action-box .title {
            font-weight: bold;
            color: #1e40af;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .action-box ul {
            margin-left: 20px;
            color: #1e3a8a;
            font-size: 14px;
        }

        .action-box li {
            margin-bottom: 6px;
        }

        .contact-section {
            background-color: #f3f4f6;
            padding: 20px;
            margin: 24px 0;
            border-radius: 6px;
            font-size: 13px;
            color: #4b5563;
        }

        .contact-section .title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .contact-info span {
            display: block;
        }

        .contact-info strong {
            color: #1f2937;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }

        .footer p {
            margin-bottom: 8px;
        }

        .confidential-warning {
            background-color: #fef9e7;
            border-left: 4px solid #eab308;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 11px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Documento Rechazado</h1>
            <p>Notificación de Verificación Administrativa</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                Estimad@<strong>{{ $beneficiario_genero === 'M' ? 'o' : 'a' }} {{ $beneficiario_nombre }}</strong>,
            </div>

            <!-- Main Alert -->
            <div class="alert-box">
                <div class="label">Notificación Importante</div>
                <div class="message">
                    El documento <strong>"{{ $documento_nombre }}"</strong> ha sido rechazado durante la revisión administrativa.
                </div>
            </div>

            <!-- Details -->
            <table class="info-table">
                <tr>
                    <td>Folio de Solicitud:</td>
                    <td><strong>{{ $folio }}</strong></td>
                </tr>
                <tr>
                    <td>Programa/Apoyo:</td>
                    <td>{{ $apoyo_nombre }}</td>
                </tr>
                <tr>
                    <td>Documento Rechazado:</td>
                    <td>{{ $documento_nombre }}</td>
                </tr>
                <tr>
                    <td>Fecha de Rechazo:</td>
                    <td>{{ $fecha_rechazo }}</td>
                </tr>
            </table>

            <!-- Rejection Reason -->
            <div class="section-title">Motivo del Rechazo</div>
            <div class="reason-box">
                <div class="label">Explicación Detallada</div>
                <div class="text">{{ $motivo }}</div>
            </div>

            <!-- Next Steps -->
            <div class="action-box">
                <div class="title">¿Qué puedo hacer?</div>
                <ul>
                    <li><strong>Revisar el documento:</strong> Verifica que cumpla con todos los requisitos especificados.</li>
                    <li><strong>Corregir y reenviar:</strong> Si es posible, ajusta el documento según el motivo del rechazo.</li>
                    <li><strong>Contactar soporte:</strong> Si tienes dudas sobre el rechazo, comunícate con nosotros.</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="contact-section">
                <div class="title">Información de Contacto y Soporte</div>
                <div class="contact-info">
                    <span><strong>Correo Electrónico:</strong> {{ $soporte_email }}</span>
                    <span><strong>Teléfono:</strong> {{ $soporte_telefono }}</span>
                    <span><strong>Horario de Atención:</strong> {{ $soporte_horario }}</span>
                </div>
            </div>

            <!-- Confidentiality Warning -->
            <div class="confidential-warning">
                <strong>Aviso de Confidencialidad:</strong> Este correo contiene información confidencial. Conforme a la Ley General de Protección de Datos Personales (LGPDP), esta información es de uso exclusivo del destinatario. No reproduzca ni comparta sin autorización.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIGO - Sistema Integral de Gestión de Oportunidades</p>
            <p>INJUVE - Instituto Nacional de la Juventud</p>
        </div>
    </div>
</body>
</html>
