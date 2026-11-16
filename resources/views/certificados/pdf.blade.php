@php
    use App\Models\Certificate;

    $fecha = $certificado->fecha_emision ?? $certificado->created_at;
    $fechaTexto = $fecha ? $fecha->locale('es')->translatedFormat('j \d\e F \d\e Y') : '';
    $tipoTexto = Certificate::TIPOS[$certificado->tipo] ?? 'Reconocimiento';
    $motivo = Certificate::MOTIVOS[$certificado->tipo] ?? 'por su participación en';
    $horas = (int) data_get($certificado->metadata, 'horas', 0);
    $revocado = $certificado->estado === 'revocado';
    $cargoEmisor = $certificado->emisor?->getRoleNames()->first();
    $cargoEmisor = match ($cargoEmisor) {
        'Administrador' => 'Administración del Sistema de Semilleros',
        'Coordinador' => 'Coordinación de Semilleros de Investigación',
        default => 'Sistema de Gestión de Semilleros',
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado {{ $certificado->codigo }}</title>
    <style>
        /* Misma base que los reportes: DejaVu Sans (trae tildes y símbolos),
           maquetación con tablas y sin float ni inline-block, que DomPDF
           resuelve mal. */
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
        }

        /* Doble marco: azul institucional por fuera, dorado por dentro. */
        .marco-exterior {
            position: absolute;
            top: 9mm; left: 9mm; right: 9mm; bottom: 9mm;
            border: 2.2pt solid #1e3a8a;
        }
        .marco-interior {
            position: absolute;
            top: 12mm; left: 12mm; right: 12mm; bottom: 12mm;
            border: 0.8pt solid #b8902f;
        }
        .franja {
            position: absolute;
            top: 12mm; left: 12mm; right: 12mm;
            height: 4mm;
            background-color: #1e3a8a;
        }

        .contenido {
            position: absolute;
            top: 24mm; left: 26mm; right: 26mm; bottom: 20mm;
            text-align: center;
        }

        .logo { width: 22mm; height: 22mm; }
        .institucion {
            margin-top: 3mm;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 3pt;
            color: #1e3a8a;
        }
        .sistema {
            font-size: 8.5pt;
            letter-spacing: 1pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .titulo {
            margin-top: 8mm;
            font-size: 26pt;
            font-weight: bold;
            letter-spacing: 4pt;
            color: #0f172a;
        }
        .subtitulo {
            font-size: 11pt;
            letter-spacing: 3pt;
            color: #b8902f;
            text-transform: uppercase;
        }

        .otorga {
            margin-top: 7mm;
            font-size: 10.5pt;
            color: #475569;
        }
        .nombre {
            margin: 3mm auto 0 auto;
            font-size: 24pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .linea-nombre {
            width: 150mm;
            margin: 2mm auto 0 auto;
            border-top: 0.8pt solid #b8902f;
        }

        .motivo {
            margin: 6mm auto 0 auto;
            width: 210mm;
            font-size: 11.5pt;
            line-height: 1.55;
            color: #334155;
        }
        .motivo strong { color: #0f172a; }
        .descripcion {
            margin: 3mm auto 0 auto;
            width: 200mm;
            font-size: 9.5pt;
            line-height: 1.5;
            color: #64748b;
        }
        .lugar-fecha {
            margin-top: 5mm;
            font-size: 10pt;
            color: #475569;
        }

        /* DomPDF no dibuja una <table> con position:absolute; se posiciona el
           contenedor y la tabla queda en flujo normal dentro de él. */
        .pie {
            position: absolute;
            top: 163mm; left: 26mm; right: 26mm;
        }
        table.firmas {
            width: 100%;
            border-collapse: collapse;
        }
        table.firmas td {
            width: 50%;
            vertical-align: bottom;
            text-align: center;
            padding: 0 12mm;
        }
        .linea-firma {
            border-top: 0.8pt solid #334155;
            padding-top: 2mm;
            margin: 0 10mm;
        }
        .firmante { font-size: 10pt; font-weight: bold; color: #0f172a; }
        .cargo { font-size: 8pt; color: #64748b; }

        .verificacion {
            border: 0.6pt solid #cbd5e1;
            background-color: #f8fafc;
            padding: 3mm 4mm;
            margin: 0 14mm;
        }
        .verificacion .etiqueta { font-size: 7pt; letter-spacing: 1pt; color: #64748b; text-transform: uppercase; }
        .verificacion .codigo { font-size: 12pt; font-weight: bold; letter-spacing: 1pt; color: #1e3a8a; }
        .verificacion .nota { font-size: 6.5pt; color: #94a3b8; margin-top: 1mm; }

        /* Un certificado revocado se puede consultar, pero no debe poder
           pasar por válido: se marca en diagonal sobre todo el documento. */
        .revocado {
            position: absolute;
            top: 80mm; left: 0; right: 0;
            text-align: center;
            font-size: 78pt;
            font-weight: bold;
            letter-spacing: 8pt;
            color: #dc2626;
            opacity: 0.16;
            transform: rotate(-18deg);
        }
    </style>
</head>
<body>
    <div class="marco-exterior"></div>
    <div class="marco-interior"></div>
    <div class="franja"></div>

    <div class="contenido">
        @if(!empty($logo))
            <img class="logo" src="{{ $logo }}" alt="">
        @endif
        <div class="institucion">UNIVERSIDAD MARIANA</div>
        <div class="sistema">Sistema de Gestión de Semilleros de Investigación</div>

        <div class="titulo">CERTIFICADO</div>
        <div class="subtitulo">de {{ $tipoTexto }}</div>

        <div class="otorga">Se otorga el presente certificado a</div>
        <div class="nombre">{{ $certificado->usuario->name ?? 'Destinatario' }}</div>
        <div class="linea-nombre"></div>

        @php
            // Se arma en una sola cadena: con varios @if en líneas separadas
            // Blade deja espacios sueltos antes de la coma y del punto final.
            $objeto = $certificado->evento
                ? 'el evento <strong>«' . e($certificado->evento->titulo) . '»</strong>'
                : '<strong>' . e($certificado->titulo) . '</strong>';
            $textoHoras = $horas > 0
                ? ', con una intensidad de <strong>' . $horas . ' ' . ($horas === 1 ? 'hora' : 'horas') . '</strong>'
                : '';
        @endphp
        <div class="motivo">{{ ucfirst($motivo) }} {!! $objeto !!}{!! $textoHoras !!}.</div>

        @if($certificado->descripcion)
            <div class="descripcion">{{ $certificado->descripcion }}</div>
        @endif

        <div class="lugar-fecha">Dado en San Juan de Pasto, el {{ $fechaTexto }}.</div>

    </div>

    <div class="pie">
        <table class="firmas">
            <tr>
                <td>
                    <div class="linea-firma">
                        <div class="firmante">{{ $certificado->emisor->name ?? 'Coordinación' }}</div>
                        <div class="cargo">{{ $cargoEmisor }}</div>
                    </div>
                </td>
                <td>
                    <div class="verificacion">
                        <div class="etiqueta">Código de verificación</div>
                        <div class="codigo">{{ $certificado->codigo }}</div>
                        <div class="nota">Este código identifica el certificado en el sistema.</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($revocado)
        <div class="revocado">REVOCADO</div>
    @endif
</body>
</html>
