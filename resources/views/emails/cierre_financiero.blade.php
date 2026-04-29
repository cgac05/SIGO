<!DOCTYPE html>
<html>
<head>
    <title>Notificación de Cierre Financiero</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h2 style="color: #064e3b;">Notificación de Cierre Financiero</h2>
    
    <p>Hola <strong>{{ $solicitud->nombre }}</strong>,</p>
    
    <p>Nos complace informarte que se ha registrado el pago/cierre financiero correspondiente a tu solicitud.</p>
    
    <div style="background-color: #f0fdf4; border-left: 4px solid #059669; padding: 15px; margin: 20px 0;">
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 10px;"><strong>Folio:</strong> #{{ $solicitud->folio }}</li>
            <li style="margin-bottom: 10px;"><strong>Apoyo:</strong> {{ $solicitud->nombre_apoyo }}</li>
            <li style="margin-bottom: 10px;"><strong>Monto Registrado:</strong> ${{ number_format($solicitud->monto_entregado, 2) }}</li>
        </ul>
    </div>
    
    <p>Si tienes alguna duda o aclaración, por favor contáctanos.</p>
    
    <p>Atentamente,<br><strong>El equipo de Finanzas - SIGO</strong></p>
</body>
</html>
