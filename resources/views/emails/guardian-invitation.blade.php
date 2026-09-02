<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: #1a1a2e; padding: 30px; text-align: center; }
        .header h1 { color: #e94560; margin: 0; font-size: 24px; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .content h2 { color: #1a1a2e; margin-top: 0; }
        .btn { display: inline-block; background-color: #e94560; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: bold; margin: 20px 0; }
        .footer { background-color: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #666666; }
        .note { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lighthouse</h1>
        </div>
        <div class="content">
            <h2>Hola,</h2>
            <p>Has sido invitado como <strong>guardian</strong> de <strong>{{ $participantName }}</strong> en <strong>{{ $tenantName }}</strong>.</p>
            <p>Para acceder a la plataforma y ver el progreso de {{ $participantName }}, necesitas establecer tu contraseña.</p>
            <p style="text-align: center;">
                <a href="{{ url('/accept-invitation?token=' . $invitationToken) }}" class="btn">Aceptar Invitación</a>
            </p>
            <div class="note">
                <strong>Nota:</strong> Este enlace expira en 7 días. Si no puedes hacer click en el botón, copia y pega el siguiente enlace en tu navegador:<br>
                <small>{{ url('/accept-invitation?token=' . $invitationToken) }}</small>
            </div>
        </div>
        <div class="footer">
            <p>Este email fue enviado por Lighthouse - Sistema de Gestion Educativa</p>
        </div>
    </div>
</body>
</html>
