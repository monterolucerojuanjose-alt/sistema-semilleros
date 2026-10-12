<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualización de Estado de Inscripción</title>
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
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }
        .status-aceptado {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-rechazado {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .status-revision {
            background-color: #fef3c7;
            color: #d97706;
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
        .observaciones {
            background-color: #fffbeb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
        }
        .observaciones h3 {
            color: #d97706;
            margin-top: 0;
        }
        .success-message {
            background-color: #d1fae5;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #10b981;
            margin: 20px 0;
        }
        .rejection-message {
            background-color: #fee2e2;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ef4444;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if($inscripcion->estado === 'Aceptado')
                    ✅ Inscripción Aceptada
                @elseif($inscripcion->estado === 'Rechazado')
                    ❌ Inscripción Rechazada
                @else
                    📋 Estado Actualizado
                @endif
            </h1>
            <p>Encuentro Institucional de Semilleros de Investigación</p>
        </div>

        <p>Estimado/a <strong>{{ $inscripcion->proyecto->autor->name }}</strong>,</p>

        <p>Le informamos que el estado de su inscripción ha sido actualizado.</p>

        <div class="status-badge status-{{ strtolower(str_replace(' ', '', $inscripcion->estado)) }}">
            Nuevo Estado: {{ $inscripcion->estado }}
        </div>

        <div class="info-section">
            <h3>📋 Detalles de la Inscripción</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Código:</div>
                    <div class="info-value"><strong>{{ $inscripcion->codigo_inscripcion }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Proyecto:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->titulo }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">{{ $inscripcion->tipo === 'ponencia' ? 'Ponencia' : 'Póster' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado Anterior:</div>
                    <div class="info-value">{{ $estadoAnterior }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado Actual:</div>
                    <div class="info-value"><strong>{{ $inscripcion->estado }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Actualización:</div>
                    <div class="info-value">{{ $fecha }}</div>
                </div>
            </div>
        </div>

        @if($inscripcion->estado === 'Aceptado')
        <div class="success-message">
            <h3>🎉 ¡Felicitaciones!</h3>
            <p>Su inscripción ha sido <strong>aceptada</strong> para participar en el Encuentro Institucional de Semilleros de Investigación.</p>
            <p>Próximos pasos:</p>
            <ul>
                <li>Recibirá información detallada sobre la presentación</li>
                <li>Se le asignará fecha y hora específica</li>
                <li>Podrá descargar su certificado de participación</li>
            </ul>
        </div>
        @elseif($inscripcion->estado === 'Rechazado')
        <div class="rejection-message">
            <h3>📝 Evaluación Completada</h3>
            <p>Su inscripción ha sido <strong>rechazada</strong> por el comité evaluador.</p>
            <p>No se desanime, puede mejorar su propuesta y participar en futuros eventos.</p>
        </div>
        @endif

        @if($inscripcion->observaciones)
        <div class="observaciones">
            <h3>📝 Observaciones del Comité Evaluador</h3>
            <p>{{ $inscripcion->observaciones }}</p>
        </div>
        @endif

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
