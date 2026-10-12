<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .message {
            background-color: @if($tipo == 'exito') #d4edda @elseif($tipo == 'error') #f8d7da @elseif($tipo == 'alerta') #fff3cd @else #d1ecf1 @endif;
            border: 1px solid @if($tipo == 'exito') #c3e6cb @elseif($tipo == 'error') #f5c6cb @elseif($tipo == 'alerta') #ffeaa7 @else #bee5eb @endif;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .message h2 {
            margin: 0 0 15px 0;
            color: @if($tipo == 'exito') #155724 @elseif($tipo == 'error') #721c24 @elseif($tipo == 'alerta') #856404 @else #0c5460 @endif;
            font-size: 18px;
        }
        .inscripcion-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .inscripcion-info h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .inscripcion-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .detail-item {
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #007bff;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
        }
        .detail-value {
            color: #2c3e50;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-aceptado { background-color: #d4edda; color: #155724; }
        .status-rechazado { background-color: #f8d7da; color: #721c24; }
        .status-revision { background-color: #d1ecf1; color: #0c5460; }
        .status-inscrito { background-color: #fff3cd; color: #856404; }
        .tipo-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .tipo-ponencia { background-color: #e2e3e5; color: #383d41; }
        .tipo-poster { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $titulo }}</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $usuario->name }}</strong>,</p>
            
            <div class="message">
                <h2>{{ $titulo }}</h2>
                <p>{{ $mensaje }}</p>
            </div>

            <div class="inscripcion-info">
                <h3>🎯 Detalles de la Inscripción</h3>
                
                <div class="inscripcion-details">
                    <div class="detail-item">
                        <div class="detail-label">Código de Inscripción</div>
                        <div class="detail-value">{{ $inscripcion->codigo_inscripcion }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tipo de Participación</div>
                        <div class="detail-value">
                            <span class="tipo-badge tipo-{{ $inscripcion->tipo }}">
                                {{ ucfirst($inscripcion->tipo) }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estado Actual</div>
                        <div class="detail-value">
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $inscripcion->estado)) }}">
                                {{ $inscripcion->estado }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Fecha de Inscripción</div>
                        <div class="detail-value">{{ $inscripcion->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h4>📋 Proyecto Inscrito</h4>
                    <div class="detail-item" style="margin-top: 10px;">
                        <div class="detail-label">Título del Proyecto</div>
                        <div class="detail-value">{{ $proyecto->titulo }}</div>
                    </div>
                    <div class="detail-item" style="margin-top: 10px;">
                        <div class="detail-label">Código del Proyecto</div>
                        <div class="detail-value">{{ $proyecto->codigo }}</div>
                    </div>
                    @if($semillero)
                        <div class="detail-item" style="margin-top: 10px;">
                            <div class="detail-label">Semillero</div>
                            <div class="detail-value">{{ $semillero->nombre }}</div>
                        </div>
                    @endif
                </div>

                @if($estadoAnterior)
                    <div style="margin-top: 15px;">
                        <div class="detail-label">Estado Anterior</div>
                        <div class="detail-value">
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $estadoAnterior)) }}">
                                {{ $estadoAnterior }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <p>Puedes revisar todos los detalles de tu inscripción en tu panel de usuario.</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/inscripciones/' . $inscripcion->id) }}" class="btn">Ver Inscripción</a>
                <a href="{{ url('/dashboard') }}" class="btn">Ir al Dashboard</a>
            </div>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático del <strong>Sistema de Semilleros</strong>.</p>
            <p>Si tienes alguna pregunta sobre tu inscripción, no dudes en contactar a tu coordinador.</p>
            <p>© {{ date('Y') }} Sistema de Semilleros. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
