<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc2626; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9fafb; padding: 20px; border: 1px solid #d1d5db; }
        .footer { background-color: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table td { padding: 10px; border: 1px solid #d1d5db; }
        table td:first-child { background-color: #f3f4f6; font-weight: bold; }
        .warning { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
        .motivos { background-color: #ffffff; padding: 15px; border: 1px solid #e5e7eb; border-radius: 5px; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">📋 Notificación de Rechazo de Solicitud</h2>
        </div>
        
        <div class="content">
            <p>Estimado(a) <strong>{{ $beneficiario_nombre }}</strong>,</p>
            
            <p>Después de revisar detalladamente su solicitud, se ha tomado la decisión de <strong style="color: #dc2626;">RECHAZAR</strong> su participación en el programa.</p>
            
            <h3>Detalles de la Solicitud</h3>
            <table>
                <tr>
                    <td><strong>Folio</strong></td>
                    <td>#{{ $folio }}</td>
                </tr>
                <tr>
                    <td><strong>Programa</strong></td>
                    <td>{{ $apoyo_nombre }}</td>
                </tr>
                <tr>
                    <td><strong>Fecha de Rechazo</strong></td>
                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
            
            <h3>Motivos del Rechazo</h3>
            <div class="motivos">
                @if($motivo_directivo && !empty(trim($motivo_directivo)))
                    <p><strong>Comentario del Directivo:</strong></p>
                    <p>{{ $motivo_directivo }}</p>
                @else
                    <p>Su solicitud no cumplió con los requisitos mínimos requeridos para la aprobación. Esto puede deberse a:</p>
                    <ul>
                        @foreach($motivos_generales as $motivo)
                            <li>{{ $motivo }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            
            <div class="warning">
                <p style="margin: 0;"><strong>⚠️ Importante:</strong> Esta decisión es DEFINITIVA y no podrá ser modificada. Si considera que ha habido un error, puede contactar a nuestra oficina administrativa para solicitar una revisión de su caso dentro de los próximos 10 días hábiles.</p>
            </div>
            
            <h3>Próximos Pasos</h3>
            <p>Para cualquier duda o aclaración, puede comunicarse con:</p>
            <ul>
                <li><strong>Email:</strong> soporte@sigo.gob.mx</li>
                <li><strong>Teléfono:</strong> +52 (311) 2330853</li>
                <li><strong>Horario de atención:</strong> Lunes a Viernes, 9:00 AM - 5:00 PM</li>
            </ul>
        </div>
        
        <div class="footer">
            <p style="margin: 0;"><strong>Sistema SIGO</strong> - Gestión de Solicitudes y Apoyos</p>
            <p style="margin: 5px 0 0 0;">Correo generado automáticamente. Por favor no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>

