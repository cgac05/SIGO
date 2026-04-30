<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, sans-serif; color: #0f172a; }
        .container { max-width: 640px; margin: 0 auto; padding: 24px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08); }
        .header { background: linear-gradient(135deg, #0f766e 0%, #155e75 100%); color: #fff; padding: 28px 32px; }
        .header h2 { margin: 0; font-size: 22px; line-height: 1.3; }
        .content { padding: 32px; background: #ffffff; }
        .content p { margin: 0 0 16px; line-height: 1.7; color: #334155; }
        .code-box { margin: 24px 0; padding: 18px 20px; text-align: center; border-radius: 12px; border: 1px solid #bae6fd; background: #eff6ff; color: #0f172a; font-size: 32px; font-weight: 800; letter-spacing: 0.5em; }
        .note { margin-top: 24px; padding: 16px 18px; border-left: 4px solid #0f766e; background: #f8fafc; border-radius: 10px; }
        .note p { margin: 0; font-size: 14px; color: #475569; }
        .footer { padding: 18px 32px 28px; font-size: 12px; color: #64748b; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .footer strong { color: #0f172a; }
        @media only screen and (max-width: 640px) {
            .container { padding: 12px; }
            .content, .header, .footer { padding-left: 20px; padding-right: 20px; }
            .code-box { font-size: 24px; letter-spacing: 0.3em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2>Confirmación de restablecimiento de contraseña</h2>
            </div>

            <div class="content">
                <p>Estimado(a) <strong>{{ $nombre }}</strong>:</p>

                <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en el sistema SIGO. Para continuar, utilice el siguiente código de verificación de 6 dígitos:</p>

                <div class="code-box">{{ $codigo }}</div>

                <p>Este código tiene una vigencia aproximada de <strong>{{ $minutosVigencia }} minutos</strong>. Una vez validado, podrá definir una nueva contraseña de acceso.</p>

                <div class="note">
                    <p>Si usted no realizó esta solicitud, puede ignorar este mensaje. Por seguridad, no comparta este código con terceros.</p>
                </div>
            </div>

            <div class="footer">
                <p style="margin: 0 0 6px;"><strong>Sistema SIGO</strong> - Gestión de Solicitudes y Apoyos</p>
                <p style="margin: 0;">Correo generado automáticamente. Por favor no responda a este mensaje.</p>
            </div>
        </div>
    </div>
</body>
</html>