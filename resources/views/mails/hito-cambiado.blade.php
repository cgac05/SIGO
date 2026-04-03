<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progreso en tu Solicitud</title>
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
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
        .milestone-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border-left: 4px solid #059669;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        .milestone-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .milestone-name {
            font-size: 20px;
            font-weight: bold;
            color: #047857;
            margin-bottom: 10px;
        }
        .milestone-description {
            color: #065f46;
            font-size: 14px;
        }
        .timeline {
            position: relative;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 4px;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        .timeline-marker {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            font-weight: bold;
        }
        .timeline-item.active .timeline-marker {
            background-color: #059669;
            box-shadow: 0 0 0 3px #d1fae5;
        }
        .timeline-item.inactive .timeline-marker {
            background-color: #d1d5db;
        }
        .timeline-item.active .timeline-label {
            font-weight: bold;
            color: #047857;
        }
        .timeline-item.inactive .timeline-label {
            color: #9ca3af;
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
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box .label {
            font-weight: bold;
            color: #0c4a6e;
            margin-bottom: 5px;
        }
        .info-box .message {
            color: #164e63;
            font-size: 14px;
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
            <h1>✅ Progreso en tu Solicitud</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                <p>Estimad@{{ $beneficiario->genero === 'M' ? 'o' : 'a' }} <strong>{{ $beneficiario->nombre }}</strong>,</p>
            </div>

            <!-- Milestone -->
            <div class="milestone-box">
                <div class="milestone-icon">
                    @switch($hito->tipo)
                        @case(1)
                            📢
                            @break
                        @case(2)
                            📥
                            @break
                        @case(3)
                            📋
                            @break
                        @case(4)
                            🎯
                            @break
                        @case(5)
                            ✔️
                            @break
                        @default
                            📍
                    @endswitch
                </div>
                <div class="milestone-name">{{ $nombreHito }}</div>
                <div class="milestone-description">
                    Tu solicitud ha avanzado a esta etapa del proceso
                </div>
            </div>

            <!-- Timeline -->
            <div class="timeline">
                <h3 style="margin-top: 0; color: #111827;">Progreso del Proceso</h3>
                <div class="timeline-item {{ $hito->tipo >= 1 ? 'active' : 'inactive' }}">
                    <div class="timeline-marker">1</div>
                    <div class="timeline-label">Publicación</div>
                </div>
                <div class="timeline-item {{ $hito->tipo >= 2 ? 'active' : 'inactive' }}">
                    <div class="timeline-marker">2</div>
                    <div class="timeline-label">Recepción</div>
                </div>
                <div class="timeline-item {{ $hito->tipo >= 3 ? 'active' : 'inactive' }}">
                    <div class="timeline-marker">3</div>
                    <div class="timeline-label">Análisis Administrativo</div>
                </div>
                <div class="timeline-item {{ $hito->tipo >= 4 ? 'active' : 'inactive' }}">
                    <div class="timeline-marker">4</div>
                    <div class="timeline-label">Resultados</div>
                </div>
                <div class="timeline-item {{ $hito->tipo >= 5 ? 'active' : 'inactive' }}">
                    <div class="timeline-marker">5</div>
                    <div class="timeline-label">Cierre</div>
                </div>
            </div>

            <!-- Details -->
            <div class="detail-section">
                <div class="detail-row">
                    <span class="detail-label">Etapa actual:</span>
                    <span class="detail-value">{{ $nombreHito }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tipo de cambio:</span>
                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $tipo_cambio)) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha de actualización:</span>
                    <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <div class="label">📌 Información Importante</div>
                <div class="message">
                    Continuaremos monitoreando el progreso de tu solicitud. Te notificaremos cuando haya nuevos cambios en tu expediente.
                </div>
            </div>

            <!-- Call to Action -->
            <div style="text-align: center;">
                <a href="{{ url("/solicitud/{$hito->apoyo->id_solicitud}") }}" class="action-button">Ver Detalles de mi Solicitud</a>
            </div>

            <hr class="divider">

            <!-- Support Info -->
            <div style="background-color: #f3f4f6; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <p style="margin: 0; font-size: 14px;">
                    <strong>¿Necesitas más información?</strong><br>
                    Puedes acceder a tu solicitud en cualquier momento a través de tu cuenta SIGO para ver todos los detalles y documentos.
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
