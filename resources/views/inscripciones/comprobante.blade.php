<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Inscripción - {{ $inscripcion->codigo_inscripcion }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        @page { margin: 16mm 16mm 14mm 16mm; }

        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header img { width: 16mm; height: 16mm; margin-bottom: 6px; }

        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }

        .header h2 {
            color: #6b7280;
            font-size: 16px;
            margin: 5px 0 0 0;
            font-weight: normal;
        }

        .content {
            margin-bottom: 16px;
        }

        .section {
            margin-bottom: 16px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            text-transform: uppercase;
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
            padding: 5px 0;
        }

        .info-value {
            display: table-cell;
            color: #6b7280;
            padding: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-inscrito {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-en-revision,
        .status-revision {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-aceptado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-rechazado {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .footer {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }

        /* Recuadro del código de verificación (antes era un «Código QR» vacío,
           sin QR, que daba la impresión de un documento sin terminar). */
        .verificacion {
            margin: 16px auto 0 auto;
            width: 70%;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            text-align: center;
            padding: 10px;
        }
        .verificacion .etiqueta { font-size: 9px; letter-spacing: 1px; color: #64748b; text-transform: uppercase; }
        .verificacion .codigo { font-size: 16px; font-weight: bold; letter-spacing: 1px; color: #1e40af; margin-top: 2px; }
        .verificacion .nota { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        .observaciones {
            background-color: #fffbeb;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .observaciones-title {
            font-weight: bold;
            color: #d97706;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($logo))
            <img src="{{ $logo }}" alt="">
        @endif
        <h1>COMPROBANTE DE INSCRIPCIÓN</h1>
        {{-- Antes decía siempre «Encuentro Institucional…», fuera cual fuera el evento. --}}
        <h2>{{ $inscripcion->evento->titulo ?? 'Evento de semilleros de investigación' }}</h2>
    </div>

    <div class="content">
        <!-- Información de la inscripción -->
        <div class="section">
            <div class="section-title">Información de la Inscripción</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Código de Inscripción:</div>
                    <div class="info-value">{{ $inscripcion->codigo_inscripcion }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tipo de Participación:</div>
                    <div class="info-value">{{ \App\Support\Etiquetas::tipoInscripcion($inscripcion->tipo) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value">
                        {{-- Str::slug: «En revisión» → «en-revision». Antes la clase salía
                             «status-enrevisión», no existía y la insignia no tenía color. --}}
                        <span class="status-badge status-{{ \Illuminate\Support\Str::slug($inscripcion->estado) }}">{{ \App\Support\Etiquetas::estado($inscripcion->estado) }}</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Inscripción:</div>
                    <div class="info-value">{{ $inscripcion->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
            </div>
        </div>

        <!-- Información del proyecto -->
        <div class="section">
            <div class="section-title">Información del Proyecto</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Título del Proyecto:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->titulo ?? 'Proyecto eliminado' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Código del Proyecto:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->codigo }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Autor:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->autor->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Correo del autor:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->autor->email }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Semillero:</div>
                    <div class="info-value">{{ $inscripcion->proyecto->semillero->nombre ?? 'Sin semillero' }}</div>
                </div>
            </div>
        </div>

        <!-- Resumen del proyecto -->
        <div class="section">
            <div class="section-title">Resumen del Proyecto</div>
            <div style="text-align: justify; line-height: 1.6;">
                {{ $inscripcion->proyecto->resumen }}
            </div>
        </div>

        <!-- Observaciones (si las hay) -->
        @if($inscripcion->observaciones)
        <div class="observaciones">
            <div class="observaciones-title">Observaciones:</div>
            <div>{{ $inscripcion->observaciones }}</div>
        </div>
        @endif

        <div class="verificacion">
            <div class="etiqueta">Código de verificación</div>
            <div class="codigo">{{ $inscripcion->codigo_inscripcion }}</div>
            <div class="nota">Presente este código en la mesa de registro del evento.</div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Este comprobante es válido únicamente para el proyecto y participante indicados.</strong></p>
        <p>Documento generado el {{ $fecha_generacion }} · Para consultas, contacte a la coordinación de semilleros de investigación.</p>
    </div>
</body>
</html>
