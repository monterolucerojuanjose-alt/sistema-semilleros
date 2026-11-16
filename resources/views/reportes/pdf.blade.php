@php
    use App\Support\Etiquetas;
    use Illuminate\Support\Str;

    $titulos = [
        'semilleros' => 'Semilleros de Investigación',
        'proyectos' => 'Proyectos',
        'eventos' => 'Eventos Académicos',
        'inscripciones' => 'Inscripciones a Eventos',
        'evaluaciones' => 'Evaluaciones',
        'participantes' => 'Participantes',
    ];
    $tituloReporte = $titulos[$tipo_reporte] ?? Str::ucfirst($tipo_reporte);
    $totalRegistros = is_countable($datos) ? count($datos) : 0;
    $filtros_aplicados = $filtros_aplicados ?? Etiquetas::filtrosAplicados($filtros ?? []);
    $indicadores = $indicadores ?? [];

    // Clase CSS del distintivo de estado; Str::slug evita espacios y tildes
    // («En revisión» → «en-revision»), que dejaban la clase inválida.
    $claseEstado = fn ($estado) => 'estado estado-' . Str::slug((string) $estado);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ $tituloReporte }}</title>
    <style>
        /*
         * Reglas de DomPDF que esta plantilla respeta a propósito (cada una
         * se comprobó renderizando el PDF; ninguna da error, sólo sale mal):
         *  - Nada de float: reduce el ancho útil de TODAS las páginas y las
         *    filas se estiran hasta ocupar una página cada una.
         *  - Nada de display:inline-block, ni anchos en <th>, ni
         *    table-layout:fixed, ni page-break-inside en <tr>: mismo efecto.
         *  - Nada de display:grid: no existe en DomPDF; se maqueta con tablas.
         *  - DejaVu Sans y no Arial: Arial cae en Helvetica, que no tiene
         *    símbolos como «→» ni «—» y los imprime como «?».
         */
        @page { margin: 30mm 12mm 18mm 12mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            font-size: 9px;
            line-height: 1.35;
            color: #1f2937;
        }

        /* ---------- Encabezado y pie, repetidos en cada página ---------- */
        header {
            position: fixed;
            top: -22mm; left: 0; right: 0;
            height: 16mm;
            border-bottom: 1.5pt solid #1d4ed8;
        }
        header table { width: 100%; border-collapse: collapse; margin: 0; }
        header td { border: none; padding: 0; vertical-align: middle; background: none; }
        header .logo { width: 15mm; }
        header .logo img { width: 13mm; height: 13mm; }
        header .titulo { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        header .institucion { font-size: 8px; color: #64748b; margin-top: 2px; }
        header .generado { text-align: right; font-size: 7.5px; color: #64748b; line-height: 1.5; }

        footer {
            position: fixed;
            bottom: -12mm; left: 0; right: 0;
            height: 8mm;
            border-top: 0.5pt solid #e2e8f0;
            padding-top: 3px;
            font-size: 7.5px;
            color: #94a3b8;
        }
        /* «Página N de M» la dibuja NumeracionPdf sobre el lienzo: DomPDF no
           resuelve counter(pages) desde CSS. */

        /* ---------- Bloques de resumen ---------- */
        .seccion {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 12px 0 5px 0;
        }

        table.indicadores { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 4px -5px; }
        table.indicadores td {
            border: 0.5pt solid #dbeafe;
            border-top: 2pt solid #1d4ed8;
            background-color: #f8fafc;
            padding: 6px 9px;
            vertical-align: top;
        }
        table.indicadores .valor { font-size: 15px; font-weight: bold; color: #0f172a; }
        table.indicadores .etiqueta { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }

        .filtros {
            background-color: #eff6ff;
            border: 0.5pt solid #bfdbfe;
            padding: 5px 9px;
            font-size: 8px;
            color: #1e3a8a;
            margin-top: 8px;
        }
        .filtros strong { color: #1e40af; }

        /* ---------- Tablas de datos ---------- */
        table.datos { width: 100%; border-collapse: collapse; }
        /* Repite la fila de encabezado cuando la tabla ocupa varias páginas. */
        table.datos thead { display: table-header-group; }
        table.datos th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
            padding: 6px 6px;
            border: 0.5pt solid #1e3a8a;
        }
        table.datos td {
            padding: 5px 6px;
            font-size: 8px;
            border-bottom: 0.5pt solid #e2e8f0;
            vertical-align: top;
        }
        table.datos tbody tr:nth-child(even) td { background-color: #f8fafc; }
        .num { text-align: center; }
        .cons { text-align: center; color: #94a3b8; }
        .tenue { color: #94a3b8; }
        .fuerte { font-weight: bold; color: #0f172a; }
        .secundario { color: #64748b; font-size: 7px; }

        /* Distintivos de estado (sin inline-block, ver arriba). */
        .estado {
            padding: 1px 5px;
            border-radius: 6px;
            font-size: 7px;
            font-weight: bold;
            background-color: #f1f5f9;
            color: #475569;
        }
        .estado-aprobado, .estado-aceptado, .estado-activo, .estado-evaluado { background-color: #dcfce7; color: #166534; }
        .estado-rechazado { background-color: #fee2e2; color: #991b1b; }
        .estado-en-revision, .estado-pendiente { background-color: #fef3c7; color: #92400e; }
        .estado-inscrito { background-color: #dbeafe; color: #1e40af; }
        .estado-finalizado { background-color: #e0e7ff; color: #3730a3; }
        .estado-inactivo { background-color: #f1f5f9; color: #64748b; }

        .nota-alta { color: #166534; font-weight: bold; }
        .nota-media { color: #92400e; font-weight: bold; }
        .nota-baja { color: #991b1b; font-weight: bold; }

        .sin-datos {
            text-align: center;
            padding: 28px;
            color: #94a3b8;
            font-size: 10px;
            border: 0.5pt dashed #cbd5e1;
            margin-top: 10px;
        }

        .cierre {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 0.5pt solid #e2e8f0;
            font-size: 7.5px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <table>
            <tr>
                @if(!empty($logo))
                    <td class="logo"><img src="{{ $logo }}" alt=""></td>
                @endif
                <td>
                    <div class="titulo">Reporte de {{ $tituloReporte }}</div>
                    <div class="institucion">Universidad Mariana · Sistema de Gestión de Semilleros de Investigación</div>
                </td>
                <td class="generado">
                    Generado el {{ $fecha_generacion }}<br>
                    {{ $totalRegistros }} {{ $totalRegistros === 1 ? 'registro' : 'registros' }}
                </td>
            </tr>
        </table>
    </header>

    <footer>Universidad Mariana · Sistema de Gestión de Semilleros de Investigación · Documento generado automáticamente</footer>

    {{-- Indicadores --}}
    @if(count($indicadores) > 0)
        <table class="indicadores">
            <tr>
                @foreach($indicadores as $indicador)
                    {{-- Ancho en <td> de una tabla de una sola fila: aquí sí es
                         seguro (el problema de DomPDF es con <th> en tablas largas). --}}
                    <td style="width: {{ floor(100 / count($indicadores)) }}%">
                        <div class="valor">{{ $indicador['valor'] }}</div>
                        <div class="etiqueta">{{ $indicador['etiqueta'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="filtros">
        @if(count($filtros_aplicados) > 0)
            <strong>Filtros aplicados:</strong>
            @foreach($filtros_aplicados as $etiqueta => $valor)
                {{ $etiqueta }}: <strong>{{ $valor }}</strong>@if(!$loop->last) &nbsp;·&nbsp; @endif
            @endforeach
        @else
            <strong>Sin filtros:</strong> el reporte incluye todos los registros.
        @endif
    </div>

    {{-- Resumen por línea temática (sólo cuando se filtra por evento) --}}
    @if(!empty($agrupado_por_programa) && count($agrupado_por_programa) > 0)
        <div class="seccion">Resumen por línea temática</div>
        <table class="datos">
            <thead>
                <tr>
                    <th>Línea temática / área</th>
                    <th class="num">Cantidad</th>
                    @if($tipo_reporte === 'evaluaciones')<th class="num">Nota promedio</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($agrupado_por_programa as $grupo)
                    <tr>
                        <td>{{ $grupo['programa'] ?: 'Sin línea temática' }}</td>
                        <td class="num">{{ $grupo['cantidad'] }}</td>
                        @if($tipo_reporte === 'evaluaciones')
                            <td class="num">{{ isset($grupo['promedio_nota']) ? Etiquetas::nota($grupo['promedio_nota']) : '—' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="seccion">Detalle</div>

    @if($totalRegistros === 0)
        <div class="sin-datos">No se encontraron registros con los filtros aplicados.</div>
    @elseif($tipo_reporte === 'semilleros')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Semillero</th>
                    <th>Línea temática</th>
                    <th>Tutor</th>
                    <th>Coordinador</th>
                    <th>Estado</th>
                    <th class="num">Integrantes</th>
                    <th class="num">Proyectos</th>
                    <th>Creación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $semillero)
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td class="fuerte">{{ $semillero->nombre }}</td>
                        <td>{{ $semillero->linea_tematica ?: 'Sin línea temática' }}</td>
                        <td>{{ $semillero->tutor->name ?? 'Sin asignar' }}</td>
                        <td>{!! $semillero->coordinador ? e($semillero->coordinador->name) : '<span class="tenue">Sin asignar</span>' !!}</td>
                        <td><span class="{{ $claseEstado($semillero->estado) }}">{{ Etiquetas::estado($semillero->estado) }}</span></td>
                        <td class="num">{{ (int) $semillero->miembros_aprobados_count }}</td>
                        <td class="num">{{ (int) $semillero->proyectos_count }}</td>
                        <td>{{ optional($semillero->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'eventos')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Evento</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th class="num">Cupo</th>
                    <th class="num">Participantes</th>
                    <th class="num">Proyectos inscritos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $evento)
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td class="fuerte">{{ $evento->titulo }}</td>
                        <td>{{ optional($evento->fecha_inicio)->format('d/m/Y H:i') }}</td>
                        <td>{{ optional($evento->fecha_fin)->format('d/m/Y H:i') }}</td>
                        <td>{{ $evento->ubicacion ?: 'No especificada' }}</td>
                        <td><span class="{{ $claseEstado($evento->estado) }}">{{ Etiquetas::estado($evento->estado) }}</span></td>
                        <td class="num">{{ $evento->cupo_maximo ?? 'Sin límite' }}</td>
                        <td class="num">{{ (int) $evento->participantes_count }}</td>
                        <td class="num">{{ (int) $evento->inscripciones_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'proyectos')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Semillero</th>
                    <th>Estado</th>
                    <th>Creación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $proyecto)
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td>{{ $proyecto->codigo ?: '—' }}</td>
                        <td class="fuerte">{{ $proyecto->titulo }}</td>
                        <td>{{ $proyecto->autor->name ?? 'Sin autor' }}</td>
                        <td>
                            {{ $proyecto->semillero->nombre ?? 'Sin semillero' }}
                            @if($proyecto->semillero?->linea_tematica)
                                <br><span class="secundario">{{ $proyecto->semillero->linea_tematica }}</span>
                            @endif
                        </td>
                        <td><span class="{{ $claseEstado($proyecto->estado) }}">{{ Etiquetas::estado($proyecto->estado) }}</span></td>
                        <td>{{ optional($proyecto->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'inscripciones')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Código</th>
                    <th>Proyecto</th>
                    <th>Autor</th>
                    <th>Evento</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Inscripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $inscripcion)
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td>{{ $inscripcion->codigo_inscripcion }}</td>
                        <td class="fuerte">{{ $inscripcion->proyecto->titulo ?? 'Proyecto eliminado' }}</td>
                        <td>{{ $inscripcion->proyecto->autor->name ?? 'Sin autor' }}</td>
                        <td>{{ $inscripcion->evento->titulo ?? 'Sin evento' }}</td>
                        <td>{{ Etiquetas::tipoInscripcion($inscripcion->tipo) }}</td>
                        <td><span class="{{ $claseEstado($inscripcion->estado) }}">{{ Etiquetas::estado($inscripcion->estado) }}</span></td>
                        <td>{{ optional($inscripcion->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'evaluaciones')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Proyecto</th>
                    <th>Autor</th>
                    <th>Modalidad</th>
                    <th>Jurado</th>
                    <th>Rúbrica / evento</th>
                    <th class="num">Nota (0-100)</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                {{-- Los datos llegan normalizados: incluyen tanto las evaluaciones
                     con rúbrica como las de propuestas de eventos. --}}
                @foreach($datos as $evaluacion)
                    @php
                        $nota = $evaluacion['nota_final'];
                        $claseNota = $nota === null ? 'tenue' : ($nota >= 80 ? 'nota-alta' : ($nota >= 60 ? 'nota-media' : 'nota-baja'));
                    @endphp
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td class="fuerte">{{ $evaluacion['proyecto'] }}</td>
                        <td>{{ $evaluacion['autor'] }}</td>
                        <td>{!! in_array($evaluacion['modalidad'], ['No aplica', 'Sin registrar'], true) ? '<span class="tenue">' . e($evaluacion['modalidad']) . '</span>' : e($evaluacion['modalidad']) !!}</td>
                        <td>{{ $evaluacion['jurado'] }}</td>
                        <td>{{ $evaluacion['origen'] }}</td>
                        <td class="num {{ $claseNota }}">{{ Etiquetas::nota($nota) }}</td>
                        <td>{{ optional($evaluacion['fecha'])->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($tipo_reporte === 'participantes')
        <table class="datos">
            <thead>
                <tr>
                    <th class="num">N.º</th>
                    <th>Nombre</th>
                    <th>Correo electrónico</th>
                    <th>Estado</th>
                    <th>Semilleros</th>
                    <th>Proyectos</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos as $participante)
                    @php
                        // Se acota en PHP: una celda con muchísimo texto hace que
                        // DomPDF calcule mal la altura de la fila.
                        $susProyectos = $participante->proyectos ?? collect();
                        $semilleros = $susProyectos->pluck('semillero.nombre')->filter()->unique()->values();
                        $titulos = $susProyectos->pluck('titulo')->filter()->values();

                        $textoSemilleros = $semilleros->take(2)->implode(', ')
                            . ($semilleros->count() > 2 ? ' (+' . ($semilleros->count() - 2) . ' más)' : '');
                        $textoProyectos = $titulos->take(2)->map(fn ($t) => Str::limit($t, 45))->implode(', ')
                            . ($titulos->count() > 2 ? ' (+' . ($titulos->count() - 2) . ' más)' : '');
                    @endphp
                    <tr>
                        <td class="cons">{{ $loop->iteration }}</td>
                        <td class="fuerte">{{ $participante->name }}</td>
                        <td>{{ $participante->email }}</td>
                        <td><span class="{{ $claseEstado($participante->estado ?? 'activo') }}">{{ Etiquetas::estado($participante->estado ?? 'activo') }}</span></td>
                        <td>{!! $textoSemilleros !== '' ? e($textoSemilleros) : '<span class="tenue">Sin semillero</span>' !!}</td>
                        <td>{!! $textoProyectos !== '' ? e($textoProyectos) : '<span class="tenue">Sin proyectos</span>' !!}</td>
                        <td>{{ optional($participante->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($totalRegistros > 0)
        <div class="cierre">
            Total de registros: <strong>{{ $totalRegistros }}</strong>.
            La información corresponde al estado del sistema en la fecha de generación.
        </div>
    @endif
</body>
</html>
