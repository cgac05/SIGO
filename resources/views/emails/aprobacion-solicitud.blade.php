<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #16a34a; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9fafb; padding: 20px; border: 1px solid #d1d5db; }
        .footer { background-color: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table td { padding: 10px; border: 1px solid #d1d5db; }
        table td:first-child { background-color: #f3f4f6; font-weight: bold; }
        .success { background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; margin: 20px 0; }
        .cuv-box { background-color: #16a34a; color: white; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0; }
        .cuv-label { font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        .cuv-value { font-size: 18px; font-weight: bold; font-family: monospace; }
        .confidential { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; font-size: 12px; }
        .monto { font-size: 14px; color: #16a34a; font-weight: bold; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">Notificación de Aprobación de Solicitud</h2>
        </div>
        
        <div class="content">
            <p>Estimado(a) <strong>{{ $beneficiario_nombre }}</strong>,</p>
            
            <p>Nos complace informarle que su solicitud ha sido <strong style="color: #16a34a;">APROBADA</strong> por nuestro equipo de evaluación.</p>
            
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
                    <td><strong>Monto Aprobado</strong></td>
                    <td class="monto">${{ number_format($monto, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td><strong>Fecha de Aprobación</strong></td>
                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
            
            <div class="success">
                <p style="margin: 0;"><strong>Importante:</strong> Su solicitud ha sido firmada y autorizada. El siguiente paso es la gestión administrativa del desembolso según el cronograma del programa.</p>
            </div>
            
            <h3>Número de Comprobante de Validación (CUV)</h3>
            <p>Este es su código único de referencia para rastrear su solicitud:</p>
            <div class="cuv-box">
                <div class="cuv-label">CUV</div>
                <div class="cuv-value">{{ $cuv }}</div>
            </div>
            
            <h3>Próximos Pasos</h3>
            <ul>
                <li>Guarde el CUV para consultas futuras y seguimiento de su solicitud</li>
                <li>Los trámites administrativos se realizarán automáticamente en nuestro sistema</li>
                <li>Se le notificará cuando se realice el desembolso del apoyo</li>
                <li>En caso de requerir información, contacte a nuestra oficina con su CUV</li>
            </ul>
            
            <div class="confidential">
                <p style="margin: 0;"><strong>Confidencialidad:</strong> Este correo contiene información confidencial y personal. No reenvíe este mensaje a terceros ni comparta su número de CUV con personas no autorizadas. El sistema SIGO protege sus datos de acuerdo a la ley LGPDP.</p>
            </div>
            
            <h3>Contacto</h3>
            <p>Para cualquier duda o aclaración, puede comunicarse con:</p>
            <ul>
                <li><strong>Email:</strong> injuvesigo@gmail.com</li>
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
