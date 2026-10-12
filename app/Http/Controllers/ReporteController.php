<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\InscripcionEvento;
use App\Models\Evaluacion;
use App\Models\Certificado;
use App\Models\User;
use App\Models\Semillero;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProyectosExport;
use App\Exports\InscripcionesExport;
use App\Exports\EvaluacionesExport;
use App\Exports\ParticipantesExport;
use App\Exports\SemillerosExport;
use App\Exports\EventosExport;

class ReporteController extends Controller
{
    use \App\Support\NumeracionPdf;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar vista de reportes
     */
    public function index()
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a los reportes.');
        }

        $semilleros = Semillero::select('id', 'nombre')->get();
        $eventos = Evento::select('id', 'titulo', 'fecha_inicio', 'fecha_fin', 'estado')
            ->orderBy('fecha_inicio', 'desc')
            ->get();
        // Cada entidad tiene su propio conjunto de estados en el esquema de la
        // base de datos, por eso la lista depende del tipo de reporte elegido.
        $estadosPorTipo = [
            'semilleros' => ['activo', 'inactivo'],
            'proyectos' => ['Inscrito', 'En revisión', 'Aprobado', 'Rechazado'],
            'eventos' => ['activo', 'inactivo', 'finalizado'],
            'inscripciones' => ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado'],
            'evaluaciones' => ['Pendiente', 'Evaluado'],
            'participantes' => ['activo', 'inactivo'],
        ];
        $tipos = ['ponencia', 'poster'];

        return Inertia::render('Reportes/Index', [
            'auth' => [
                'user' => $user->load('roles')
            ],
            'semilleros' => $semilleros,
            'eventos' => $eventos,
            'estadosPorTipo' => $estadosPorTipo,
            'tipos' => $tipos,
        ]);
    }

    /**
     * Exportar reporte en PDF
     */
    public function exportarPDF(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para exportar reportes.');
        }

        $request->validate([
            'tipo_reporte' => 'required|in:semilleros,proyectos,eventos,inscripciones,evaluaciones,participantes',
            'formato' => 'required|in:pdf,excel',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'semillero_id' => 'nullable|exists:semilleros,id',
            'evento_id' => 'nullable|exists:eventos,id',
            'estado' => 'nullable|string',
            'tipo' => 'nullable|string',
        ]);

        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'semillero_id', 'evento_id', 'estado', 'tipo']);
        $tipoReporte = $request->tipo_reporte;
        $formato = $request->formato;

        if ($formato === 'pdf') {
            return $this->generarPDF($tipoReporte, $filtros);
        } else {
            return $this->generarExcel($tipoReporte, $filtros);
        }
    }

    /**
     * Generar PDF
     */
    private function generarPDF(string $tipoReporte, array $filtros): Response
    {
        [$registros, $agrupado] = $this->separarDatos($this->obtenerDatosReporte($tipoReporte, $filtros));

        $pdf = Pdf::loadView('reportes.pdf', [
            'tipo_reporte' => $tipoReporte,
            'datos' => $registros,
            'agrupado_por_programa' => $agrupado,
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('A4', 'landscape');

        $nombreArchivo = 'reporte_' . $tipoReporte . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';

        return $this->numerarPaginas($pdf)->download($nombreArchivo);
    }

    /**
     * Generar Excel
     */
    private function generarExcel(string $tipoReporte, array $filtros)
    {
        $nombreArchivo = 'reporte_' . $tipoReporte . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        switch ($tipoReporte) {
            case 'semilleros':
                return Excel::download(new SemillerosExport($filtros), $nombreArchivo);
            case 'proyectos':
                return Excel::download(new ProyectosExport($filtros), $nombreArchivo);
            case 'eventos':
                return Excel::download(new EventosExport($filtros), $nombreArchivo);
            case 'inscripciones':
                return Excel::download(new InscripcionesExport($filtros), $nombreArchivo);
            case 'evaluaciones':
                return Excel::download(new EvaluacionesExport($filtros), $nombreArchivo);
            case 'participantes':
                return Excel::download(new ParticipantesExport($filtros), $nombreArchivo);
            default:
                abort(400, 'Tipo de reporte no válido');
        }
    }

    /**
     * Separa la colección de registros de la agrupación por programa.
     *
     * obtenerDatosReporte() devuelve un arreglo con ambas cosas cuando se filtra
     * por evento, y una colección simple en caso contrario. Sin esta separación,
     * la vista PDF recorre el arreglo contenedor en lugar de los registros.
     *
     * @return array{0: mixed, 1: mixed} [registros, agrupación por programa]
     */
    private function separarDatos($datos): array
    {
        foreach (['proyectos', 'inscripciones', 'evaluaciones', 'participantes'] as $clave) {
            if (is_array($datos) && isset($datos[$clave])) {
                return [$datos[$clave], $datos['agrupado_por_programa'] ?? collect()];
            }
        }

        return [$datos, collect()];
    }

    /**
     * Obtener datos para el reporte
     */
    private function obtenerDatosReporte(string $tipoReporte, array $filtros)
    {
        switch ($tipoReporte) {
            case 'semilleros':
                return Semillero::consulta($filtros)->get();
            case 'proyectos':
                return $this->obtenerDatosProyectos($filtros);
            case 'eventos':
                return Evento::consulta($filtros)->get();
            case 'inscripciones':
                return $this->obtenerDatosInscripciones($filtros);
            case 'evaluaciones':
                return $this->obtenerDatosEvaluaciones($filtros);
            case 'participantes':
                return $this->obtenerDatosParticipantes($filtros);
            default:
                return [];
        }
    }

    /**
     * Obtener datos de proyectos
     */
    private function obtenerDatosProyectos(array $filtros)
    {
        $query = Proyecto::with(['autor', 'semillero']);

        // Si se filtra por evento, solo mostrar proyectos inscritos en ese evento
        if (!empty($filtros['evento_id'])) {
            $query->whereHas('inscripciones', function($q) use ($filtros) {
                $q->where('evento_id', $filtros['evento_id']);
            })->with(['inscripciones' => function($q) use ($filtros) {
                $q->where('evento_id', $filtros['evento_id']);
            }]);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['semillero_id'])) {
            $query->where('semillero_id', $filtros['semillero_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $proyectos = $query->orderBy('created_at', 'desc')->get();

        // Agrupar por programa (línea temática del semillero)
        if (!empty($filtros['evento_id'])) {
            $proyectosPorPrograma = $proyectos->groupBy(function($proyecto) {
                return $proyecto->semillero->linea_tematica ?? 'Sin programa';
            });

            return [
                'proyectos' => $proyectos,
                'agrupado_por_programa' => $proyectosPorPrograma->map(function($items, $programa) {
                    return [
                        'programa' => $programa,
                        'cantidad' => $items->count(),
                        'proyectos' => $items
                    ];
                })->values()
            ];
        }

        return $proyectos;
    }

    /**
     * Obtener datos de inscripciones
     */
    private function obtenerDatosInscripciones(array $filtros)
    {
        $query = InscripcionEvento::with(['proyecto.autor', 'proyecto.semillero', 'evento']);

        // Si se filtra por evento específico
        if (!empty($filtros['evento_id'])) {
            $query->where('evento_id', $filtros['evento_id']);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['semillero_id'])) {
            $query->whereHas('proyecto', function($q) use ($filtros) {
                $q->where('semillero_id', $filtros['semillero_id']);
            });
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $inscripciones = $query->orderBy('created_at', 'desc')->get();

        // Agrupar por programa (línea temática del semillero) si hay evento seleccionado
        if (!empty($filtros['evento_id'])) {
            $inscripcionesPorPrograma = $inscripciones->groupBy(function($inscripcion) {
                return $inscripcion->proyecto && $inscripcion->proyecto->semillero
                    ? $inscripcion->proyecto->semillero->linea_tematica
                    : 'Sin programa';
            });

            return [
                'inscripciones' => $inscripciones,
                'agrupado_por_programa' => $inscripcionesPorPrograma->map(function($items, $programa) {
                    return [
                        'programa' => $programa,
                        'cantidad' => $items->count(),
                        'inscripciones' => $items
                    ];
                })->values()
            ];
        }

        return $inscripciones;
    }

    /**
     * Obtener datos de evaluaciones
     */
    /**
     * Evaluaciones de las DOS vías del sistema, normalizadas a un mismo formato:
     * las de rúbrica (tabla `evaluaciones`) y las que los jurados registran
     * sobre proyectos de eventos (tabla `evento_participantes`). El reporte
     * omitía estas últimas, así que no reflejaba lo ocurrido en los eventos.
     */
    private function obtenerDatosEvaluaciones(array $filtros)
    {
        $evaluaciones = $this->evaluacionesDeRubrica($filtros)
            ->concat($this->evaluacionesDeEventos($filtros))
            ->sortByDesc('fecha')
            ->values();

        if (!empty($filtros['evento_id'])) {
            $porPrograma = $evaluaciones->groupBy(fn ($e) => $e['programa'] ?: 'Sin programa');

            return [
                'evaluaciones' => $evaluaciones,
                'agrupado_por_programa' => $porPrograma->map(fn ($items, $programa) => [
                    'programa' => $programa,
                    'cantidad' => $items->count(),
                    'promedio_nota' => round($items->avg('nota_final'), 2),
                    'evaluaciones' => $items,
                ])->values(),
            ];
        }

        return $evaluaciones;
    }

    /** Evaluaciones hechas con rúbrica sobre proyectos del catálogo. */
    private function evaluacionesDeRubrica(array $filtros)
    {
        $query = Evaluacion::with(['proyecto.autor', 'proyecto.semillero', 'jurado', 'rubrica']);

        if (!empty($filtros['evento_id'])) {
            $query->whereHas('proyecto.inscripciones', fn ($q) => $q->where('evento_id', $filtros['evento_id']));
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['semillero_id'])) {
            $query->whereHas('proyecto', fn ($q) => $q->where('semillero_id', $filtros['semillero_id']));
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->get()->map(fn ($e) => [
            'proyecto' => $e->proyecto->titulo ?? 'N/A',
            'autor' => $e->proyecto->autor->name ?? 'N/A',
            'jurado' => $e->jurado->name ?? 'N/A',
            'origen' => $e->rubrica->nombre ?? 'Rúbrica',
            'nota_final' => $e->nota_final,
            'estado' => $e->estado,
            'fecha' => $e->created_at,
            'programa' => $e->proyecto->semillero->linea_tematica ?? null,
            'modalidad' => null,
        ]);
    }

    /** Calificaciones que los jurados registran en los proyectos de eventos. */
    private function evaluacionesDeEventos(array $filtros)
    {
        // El semillero no aplica a estas propuestas: se escriben a mano en el
        // evento y no cuelgan del catálogo de proyectos.
        if (!empty($filtros['semillero_id'])) {
            return collect();
        }

        $query = \App\Models\EventoParticipante::with(['evento:id,titulo', 'usuario:id,name', 'evaluador:id,name'])
            ->where('estado_evaluacion', 'evaluado')
            ->whereNotNull('nota_final');

        if (!empty($filtros['evento_id'])) {
            $query->where('evento_id', $filtros['evento_id']);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_evaluacion', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_evaluacion', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'Evaluado') {
            return collect();
        }

        return $query->get()->map(fn ($p) => [
            'proyecto' => $p->titulo_proyecto ?: 'Proyecto sin título',
            'autor' => $p->usuario->name ?? 'N/A',
            'jurado' => $p->evaluador->name ?? 'Sin asignar',
            'origen' => 'Evento: ' . ($p->evento->titulo ?? 'N/A'),
            'nota_final' => $p->nota_final,
            'estado' => 'Evaluado',
            'fecha' => $p->fecha_evaluacion ?? $p->updated_at,
            'programa' => $p->programa_reporte,
            'modalidad' => $p->modalidad,
        ]);
    }

    /**
     * Obtener datos de participantes
     */
    private function obtenerDatosParticipantes(array $filtros)
    {
        // Si se filtra por evento, obtener participantes del evento
        if (!empty($filtros['evento_id'])) {
            $evento = Evento::with([
                'participantes.usuario.proyectos.semillero',
                'participantes.usuario.roles'
            ])->find($filtros['evento_id']);

            if (!$evento) {
                return [];
            }

            $participantes = $evento->participantes->map(function($participante) {
                return $participante->usuario;
            })->filter()->unique('id');

            if (!empty($filtros['estado'])) {
                $participantes = $participantes->where('estado', $filtros['estado']);
            }

            // Agrupar por programa
            $participantesPorPrograma = $participantes->groupBy(function($user) {
                $primerProyecto = $user->proyectos->first();
                return $primerProyecto && $primerProyecto->semillero
                    ? $primerProyecto->semillero->linea_tematica
                    : 'Sin programa';
            });

            return [
                'participantes' => $participantes,
                'agrupado_por_programa' => $participantesPorPrograma->map(function($items, $programa) {
                    return [
                        'programa' => $programa,
                        'cantidad' => $items->count(),
                        'participantes' => $items
                    ];
                })->values()
            ];
        }

        $query = User::with(['roles', 'proyectos.semillero']);

        if (!empty($filtros['semillero_id'])) {
            $query->whereHas('proyectos', function($q) use ($filtros) {
                $q->where('semillero_id', $filtros['semillero_id']);
            });
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->whereHas('roles', function($q) {
            $q->where('name', 'Estudiante');
        })->orderBy('name')->get();
    }

    /**
     * Vista previa del reporte
     */
    public function preview(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para ver reportes.');
        }

        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'semillero_id', 'evento_id', 'estado', 'tipo']);
        $tipoReporte = $request->tipo_reporte ?? 'proyectos';

        [$datosParaVista, $agrupado] = $this->separarDatos(
            $this->obtenerDatosReporte($tipoReporte, $filtros)
        );

        return response()->json([
            'datos' => $datosParaVista,
            'total' => is_countable($datosParaVista) ? count($datosParaVista) : 0,
            'agrupado_por_programa' => $agrupado,
            'filtros' => $filtros,
        ]);
    }
}
