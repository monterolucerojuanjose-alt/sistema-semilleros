<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuesta Aprobada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #28a745;
            margin-top: 0;
        }
        .detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
            margin-top: 5px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #28a745;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Felicidades! 🎉</h1>
        <p>Tu propuesta ha sido aprobada</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $estudiante->name }}</strong>,</p>

        <p>Nos complace informarte que tu propuesta de proyecto <strong>"{{ $participante->titulo_proyecto }}"</strong> ha sido <strong style="color: #28a745;">APROBADA</strong> para el evento <strong>{{ $evento->titulo }}</strong>.</p>

        <div class="info-box">
            <h3>📅 Información de tu Ponencia</h3>

            <div class="detail-row">
                <div class="detail-label">📍 Aula:</div>
                <div class="detail-value">{{ $participante->aula }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">🕒 Horario:</div>
                <div class="detail-value">{{ $participante->horario }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">📋 Programación:</div>
                <div class="detail-value">{{ $participante->programacion }}</div>
            </div>
        </div>

        <div class="info-box" style="border-left-color: #667eea;">
            <h3 style="color: #667eea;">📝 Datos del Evento</h3>

            <div class="detail-row">
                <div class="detail-label">Evento:</div>
                <div class="detail-value">{{ $evento->titulo }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Fecha de Inicio:</div>
                <div class="detail-value">{{ $evento->fecha_inicio->format('d/m/Y H:i') }}</div>
            </div>

            @if($evento->fecha_fin)
            <div class="detail-row">
                <div class="detail-label">Fecha de Fin:</div>
                <div class="detail-value">{{ $evento->fecha_fin->format('d/m/Y H:i') }}</div>
            </div>
            @endif

            <div class="detail-row">
                <div class="detail-label">Ubicación:</div>
                <div class="detail-value">{{ $evento->ubicacion ?? 'Por definir' }}</div>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="color: #667eea; margin-top: 0;">👥 Participantes del Proyecto</h3>
            @foreach($participante->participantes as $p)
            <div style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                <strong>{{ $p['nombre'] }}</strong><br>
                <span style="color: #6c757d;">{{ $p['email'] }}</span>
            </div>
            @endforeach
        </div>

        <p><strong>Recomendaciones:</strong></p>
        <ul>
            <li>Llega al menos 15 minutos antes de tu horario asignado</li>
            <li>Prepara tu material de presentación con anticipación</li>
            <li>Verifica los recursos técnicos que necesitarás</li>
            <li>Ten a mano una copia de respaldo de tu presentación</li>
        </ul>

        <p>¡Te deseamos mucho éxito en tu presentación!</p>

        <div class="footer">
            <p>Este es un correo automático del Sistema de Gestión de Semilleros</p>
            <p>Si tienes alguna pregunta, por favor contacta a los organizadores del evento.</p>
        </div>
    </div>
</body>
</html>








