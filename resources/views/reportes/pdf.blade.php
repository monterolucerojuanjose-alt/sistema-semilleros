<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de {{ ucfirst($tipo_reporte) }}</title>
    <style>
        /* DejaVu Sans viene incluida con DomPDF y sí contiene los acentos y los
           símbolos (→, ») que la fuente Helvetica por defecto imprime como "?". */
        @page { margin: 26mm 12mm 20mm 12mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            font-size: 10px;
            color: #1f2937;
        }

        /* Encabezado y pie repetidos en todas las páginas */
        header {
            position: fixed;
            top: -18mm; left: 0; right: 0;
            height: 16mm;
            border-bottom: 1.5pt solid #1d4ed8;
        }
        header .titulo {
            font-size: 15px;
            font-weight: bold;
            color: #1d4ed8;
        }
        header .institucion {
            font-size: 8.5px;
            color: #6b7280;
            margin-top: 2px;
        }
        header .generado {
            position: absolute;
            top: 2mm; right: 0;
            text-align: right;
            font-size: 8px;
            color: #6b7280;
        }

        footer {
            position: fixed;
            bottom: -14mm; left: 0; right: 0;
            height: 10mm;
            border-top: 0.5pt solid #e5e7eb;
            padding-top: 3px;
            font-size: 8px;
            color: #9ca3af;
        }
        footer .izq { float: left; }
        /* La numeración "Página N de M" la dibuja NumeracionPdf sobre el lienzo:
           DomPDF no resuelve counter(pages) desde CSS. */

        .resumen {
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            border-left: 2.5pt solid #1d4ed8;
            padding: 7px 10px;
            margin-bottom: 12px;
        }
        .resumen h3 {
            margin: 0 0 5px 0;
            color: #1d4ed8;
            font-size: 10.5px;
        }
        .resumen p { margin: 1px 0; font-size: 9px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        /* Repite la fila de encabezado cuando la tabla ocupa varias páginas.
           No se usa page-break-inside en las filas: DomPDF envía cada fila a
           una página distinta cuando se combina con encabezado fijo. */
        thead { display: table-header-group; }

        th {
            background-color: #1d4ed8;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
            text-align: left;
            padding: 6px 7px;
            border: 0.5pt solid #1d4ed8;
        }
        td {
            padding: 5px 7px;
            font-size: 8.5px;
            border: 0.5pt solid #e5e7eb;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }
        .num { text-align: center; }

        /* Sin display:inline-block: DomPDF estira la celda a toda la altura de
           la página cuando se usa dentro de una tabla. */
        .estado {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            background-color: #f1f5f9;
            color: #475569;
        }
        .estado-aprobado,
        .estado-aceptado,
        .estado-activo,
        .estado-evaluado   { background-color: #dcfce7; color: #166534; }
        .estado-rechazado  { background-color: #fee2e2; color: #991b1b; }
        .estado-en-revision,
        .estado-pendiente  { background-color: #fef3c7; color: #92400e; }
        .estado-inscrito   { background-color: #dbeafe; color: #1e40af; }
        .estado-finalizado { background-color: #e0e7ff; color: #3730a3; }
        .estado-inactivo   { background-color: #f1f5f9; color: #64748b; }

        .sin-datos {
            text-align: center;
            padding: 25px;
            color: #9ca3af;
            font-size: 10px;
            border: 0.5pt dashed #d1d5db;
        }
    </style>
</head>
<body>
    @php
        $titulos = [
            'semilleros' => 'Semilleros de Investigación',
            'proyectos' => 'Proyectos',
            'eventos' => 'Eventos Académicos',
            'inscripciones' => 'Inscripciones a Eventos',
            'evaluaciones' => 'Evaluaciones',
            'participantes' => 'Participantes',
        ];
        $tituloReporte = $titulos[$tipo_reporte] ?? ucfirst($tipo_reporte);
        $totalRegistros = is_countable($datos) ? count($datos) : 0;
    @endphp

    <header>
        <div class="titulo">Reporte de {{ $tituloReporte }}</div>
        <div class="institucion">Universidad Mariana · Sistema de Gestión de Semilleros de Investigación</div>
        <div class="generado">
            Generado el {{ $fecha_generacion }}<br>
            {{ $totalRegistros }} {{ $totalRegistros === 1 ? 'registro' : 'registros' }}
        </div>
    </header>

    <footer>
        <span class="izq">Universidad Mariana · Sistema de Gestión de Semilleros</span>
    </footer>

    @php
        // Sólo se listan los filtros con valor; $filtros llega con claves vacías.
        $filtrosAplicados = [];
        if (!empty($filtros['fecha_inicio'])) $filtrosAplicados['Desde'] = \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y');
        if (!empty($filtros['fecha_fin']))    $filtrosAplicados['Hasta'] = \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y');
        if (!empty($filtros['estado']))       $filtrosAplicados['Estado'] = $filtros['estado'];
        if (!empty($filtros['tipo']))         $filtrosAplicados['Tipo'] = $filtros['tipo'];
        if (!empty($filtros['semillero_id'])) $filtrosAplicados['Semillero'] = optional(\App\Models\Semillero::find($filtros['semillero_id']))->nombre ?? ('#'.$filtros['semillero_id']);
        if (!empty($filtros['evento_id']))    $filtrosAplicados['Evento'] = optional(\App\Models\Evento::find($filtros['evento_id']))->titulo ?? ('#'.$filtros['evento_id']);
    @endphp

    @if(count($filtrosAplicados) > 0)
    <div class="resumen">
        <h3>Filtros aplicados</h3>
        <p>
            @foreach($filtrosAplicados as $etiqueta => $valor)<strong>{{ $etiqueta }}:</strong> {{ $valor }}@if(!$loop->last) &nbsp;·&nbsp; @endif @endforeach
        </p>
    </div>
    @endif

    @if(!empty($agrupado_por_programa) && count($agrupado_por_programa) > 0)
    <div class="resumen">
        <h3>Resumen por programa (línea temática)</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Cantidad</th>
                @if($tipo_reporte === 'evaluaciones')<th>Promedio de nota</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($agrupado_por_programa as $grupo)
            <tr>
                <td>{{ $grupo['programa'] ?: 'Sin programa' }}</td>
                <td class="num">{{ $grupo['cantidad'] }}</td>
                @if($tipo_reporte === 'evaluaciones')<td class="num">{{ $grupo['promedio_nota'] ?? 'N/A' }}</td>@endif
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="height: 10px"></div>
    @endif

    @if($totalRegistros === 0)
        <div class="sin-datos">No se encontraron registros con los filtros aplicados.</div>
    @endif

    @if($tipo_reporte === 'semilleros')
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Línea Temática</th>
                    <th>Tutor</th>
                    <th>Coordinador</th>
                    <th>Estado</th>
                    <th>Integrantes</th>
                    <th>Proyectos</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $semillero)
                <tr>
                    <td>{{ $semillero->nombre }}</td>
                    <td>{{ $semillero->linea_tematica ?? 'Sin programa' }}</td>
                    <td>{{ $semillero->tutor->name ?? 'Sin asignar' }}</td>
                    <td>{{ $semillero->coordinador->name ?? 'Sin asignar' }}</td>
                    <td>
                        <span class="estado estado-{{ \Illuminate\Support\Str::slug($semillero->estado) }}">
                            {{ $semillero->estado }}
                        </span>
                    </td>
                    <td class="num">{{ $semillero->miembros_aprobados_count }}</td>
                    <td class="num">{{ $semillero->proyectos_count }}</td>
                    <td>{{ $semillero->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'eventos')
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Cupo</th>
                    <th>Participantes</th>
                    <th>Proyectos Inscritos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $evento)
                <tr>
                    <td>{{ $evento->titulo }}</td>
                    <td>{{ optional($evento->fecha_inicio)->format('d/m/Y') }}</td>
                    <td>{{ optional($evento->fecha_fin)->format('d/m/Y') }}</td>
                    <td>{{ $evento->ubicacion ?? 'No especificada' }}</td>
                    <td>
                        <span class="estado estado-{{ \Illuminate\Support\Str::slug($evento->estado) }}">
                            {{ $evento->estado }}
                        </span>
                    </td>
                    <td class="num">{{ $evento->cupo_maximo ?? 'Sin límite' }}</td>
                    <td class="num">{{ $evento->participantes_count }}</td>
                    <td class="num">{{ $evento->inscripciones_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'proyectos')
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Semillero</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $proyecto)
                <tr>
                    <td>{{ $proyecto->codigo }}</td>
                    <td>{{ $proyecto->titulo }}</td>
                    <td>{{ $proyecto->autor->name ?? 'N/A' }}</td>
                    <td>{{ $proyecto->semillero->nombre ?? 'N/A' }}</td>
                    <td>
                        <span class="estado estado-{{ \Illuminate\Support\Str::slug($proyecto->estado) }}">
                            {{ $proyecto->estado }}
                        </span>
                    </td>
                    <td>{{ $proyecto->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'inscripciones')
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proyecto</th>
                    <th>Autor</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha Inscripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $inscripcion)
                <tr>
                    <td>{{ $inscripcion->codigo_inscripcion }}</td>
                    <td>{{ $inscripcion->proyecto->titulo ?? 'N/A' }}</td>
                    <td>{{ $inscripcion->proyecto->autor->name ?? 'N/A' }}</td>
                    <td>{{ $inscripcion->tipo }}</td>
                    <td>
                        <span class="estado estado-{{ \Illuminate\Support\Str::slug($inscripcion->estado) }}">
                            {{ $inscripcion->estado }}
                        </span>
                    </td>
                    <td>{{ $inscripcion->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'evaluaciones')
        <table>
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Autor</th>
                    <th>Modalidad</th>
                    <th>Jurado</th>
                    <th>Rúbrica / Evento</th>
                    <th>Nota (0–100)</th>
                    <th>Fecha Evaluación</th>
                </tr>
            </thead>
            <tbody>
                {{-- Los datos llegan normalizados: incluyen tanto las evaluaciones
                     con rúbrica como las de proyectos de eventos. --}}
                @foreach($datos as $evaluacion)
                <tr>
                    <td>{{ $evaluacion['proyecto'] }}</td>
                    <td>{{ $evaluacion['autor'] }}</td>
                    <td>{{ $evaluacion['modalidad'] ?? '' }}</td>
                    <td>{{ $evaluacion['jurado'] }}</td>
                    <td>{{ $evaluacion['origen'] }}</td>
                    <td class="num">{{ $evaluacion['nota_final'] !== null ? number_format((float) $evaluacion['nota_final'], 2) : 'N/A' }}</td>
                    <td>{{ optional($evaluacion['fecha'])->format('d/m/Y') ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'participantes')
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Semillero</th>
                    <th>Proyectos</th>
                    <th>Fecha Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $participante)
                @php
                    // El contenido se arma y se acota en PHP. DomPDF calcula mal
                    // la altura de una fila cuando una celda lleva mucho texto y
                    // acaba estirándola hasta ocupar la página entera, así que se
                    // limita la longitud y se indica el resto como recuento.
                    $susSemilleros = $participante->proyectos->pluck('semillero.nombre')->filter()->unique()->values();
                    $susProyectos = $participante->proyectos->pluck('titulo')->filter()->values();

                    $textoSemilleros = $susSemilleros->take(2)->implode(', ');
                    if ($susSemilleros->count() > 2) {
                        $textoSemilleros .= ' (+' . ($susSemilleros->count() - 2) . ')';
                    }

                    $textoProyectos = $susProyectos->take(2)->map(fn ($t) => \Illuminate\Support\Str::limit($t, 40))->implode(', ');
                    if ($susProyectos->count() > 2) {
                        $textoProyectos .= ' (+' . ($susProyectos->count() - 2) . ')';
                    }
                @endphp
                <tr>
                    <td>{{ \Illuminate\Support\Str::limit($participante->name, 30) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($participante->email, 32) }}</td>
                    <td><span class="estado estado-{{ \Illuminate\Support\Str::slug($participante->estado) }}">{{ $participante->estado }}</span></td>
                    <td>{{ $textoSemilleros ?: 'Sin semillero' }}</td>
                    <td>{{ $textoProyectos ?: 'Sin proyectos' }}</td>
                    <td>{{ $participante->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($totalRegistros > 0)
    <p style="margin-top: 10px; font-size: 8.5px; color: #6b7280;">
        Total de registros: <strong>{{ $totalRegistros }}</strong>
    </p>
    @endif
</body>
</html>




















