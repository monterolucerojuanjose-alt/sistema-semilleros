<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de Inscripción</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .status-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }
        .info-section {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .info-section h3 {
            color: #2563eb;
            margin-top: 0;
            font-size: 16px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #374151;
            width: 30%;
            padding: 8px 0;
        }
        .info-value {
            display: table-cell;
            color: #6b7280;
            padding: 8px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Inscripción Confirmada</h1>
            <p>Encuentro Institucional de Semilleros de Investigación</p>
        </div>

        <p>Estimado/a <strong>{{ $inscripcion->proyecto->autor->name }}</strong>,</p>

        <p>Nos complace confirmar que su inscripción al <strong>Encuentro Institucional de Semilleros de Investigación</strong> ha sido registrada exitosamente.</p>

        <div class="status-badge">
            Estado: {{ $inscripcion->estado }}
        </div>

        <div class="info-section">
            <h3>📋 Detalles de la Inscripción</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Código:</div>
                    <div class="info-value"><strong>{{ $inscripcion->codigo_inscripcion }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">{{ $inscripcion->tipo === 'ponencia' ? 'Ponencia' : 'Póster' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Proyecto:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->titulo }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Semillero:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->semillero->nombre }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha:</div>
                    <div class="info-value">{{ $fecha }}</div>
                </div>
            </div>
        </div>

        <div class="highlight">
            <p><strong>📌 Importante:</strong></p>
            <ul>
                <li>Guarde este código de inscripción: <strong>{{ $inscripcion->codigo_inscripcion }}</strong></li>
                <li>Su inscripción está en estado <strong>"{{ $inscripcion->estado }}"</strong></li>
                <li>Recibirá notificaciones sobre el estado de su inscripción</li>
                <li>Puede descargar su comprobante desde el sistema</li>
            </ul>
        </div>

        <p>Próximos pasos:</p>
        <ol>
            <li>Su inscripción será revisada por el comité evaluador</li>
            <li>Recibirá una notificación cuando se actualice el estado</li>
            <li>Si es aceptada, recibirá información sobre la presentación</li>
        </ol>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/mis-inscripciones" class="cta-button">
                Ver Mis Inscripciones
            </a>
        </div>

        <div class="footer">
            <p>Este correo fue generado automáticamente por el Sistema de Semilleros de Investigación.</p>
            <p>Para consultas, contacte a la coordinación de semilleros.</p>
            <p><strong>{{ config('app.name') }}</strong> - {{ $fecha }}</p>
        </div>
    </div>
</body>
</html>
