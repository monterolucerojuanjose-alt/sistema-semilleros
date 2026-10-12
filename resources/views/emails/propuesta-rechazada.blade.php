<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información sobre tu Propuesta</title>
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
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #ffc107;
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
        .motivo-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
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
        .encouragement {
            background: #e7f3ff;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Información sobre tu Propuesta</h1>
        <p>{{ $evento->titulo }}</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $estudiante->name }}</strong>,</p>

        <p>Gracias por tu interés en participar en el evento <strong>{{ $evento->titulo }}</strong> con tu propuesta <strong>"{{ $participante->titulo_proyecto }}"</strong>.</p>

        <div class="info-box">
            <h3>⚠️ Estado de la Propuesta</h3>
            <p>Lamentablemente, tu propuesta no ha sido seleccionada para este evento en esta ocasión.</p>
        </div>

        <div class="motivo-box">
            <h3 style="color: #856404; margin-top: 0;">📋 Observaciones del Evaluador</h3>
            <p style="margin: 0; white-space: pre-wrap;">{{ $participante->motivo_rechazo }}</p>
        </div>

        <div class="encouragement">
            <h3 style="color: #17a2b8; margin-top: 0;">💪 ¡No te desanimes!</h3>
            <p>Te animamos a:</p>
            <ul style="margin: 10px 0;">
                <li>Tomar en cuenta las observaciones recibidas</li>
                <li>Mejorar tu propuesta con base en el feedback</li>
                <li>Estar atento a futuros eventos donde puedas participar</li>
                <li>Consultar con tus profesores o coordinadores para recibir orientación</li>
            </ul>
        </div>

        <div style="background: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="color: #667eea; margin-top: 0;">📝 Datos de tu Propuesta</h3>

            <div class="detail-row">
                <div class="detail-label">Título del Proyecto:</div>
                <div class="detail-value">{{ $participante->titulo_proyecto }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Fecha de Presentación:</div>
                <div class="detail-value">{{ $participante->created_at->format('d/m/Y H:i') }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Fecha de Revisión:</div>
                <div class="detail-value">{{ $participante->fecha_revision->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <p>Cada experiencia es una oportunidad de aprendizaje. Te animamos a seguir desarrollando tus ideas y a participar en futuros eventos.</p>

        <p><strong>¡Sigue adelante con tu investigación!</strong></p>

        <div class="footer">
            <p>Este es un correo automático del Sistema de Gestión de Semilleros</p>
            <p>Si tienes alguna pregunta, por favor contacta a los organizadores del evento.</p>
        </div>
    </div>
</body>
</html>








