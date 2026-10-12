<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Proyecto;
use App\Models\Rubrica;
use App\Models\DetalleEvaluacion;
use App\Models\CriterioRubrica;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Mostrar lista de evaluaciones
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Esta tabla es sólo para evaluaciones de proyectos de catálogo: las
        // de propuestas de evento (evento_participante_id) ya se muestran
        // aparte, en «Propuestas de evento calificadas» más abajo. Sin este
        // filtro aparecían aquí también, con el proyecto en blanco («Proyecto
        // eliminado») porque esas filas no tienen proyecto_id.
        $query = Evaluacion::with(['proyecto.autor', 'jurado', 'rubrica'])->whereNotNull('proyecto_id');

        // Filtrar según el rol del usuario
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            // Administradores y coordinadores ven todas las evaluaciones
        } elseif ($user->hasRole('Jurado')) {
            // Jurados ven solo sus propias evaluaciones
            $query->where('jurado_id', $user->id);
        } else {
            // Otros usuarios no pueden ver evaluaciones
            abort(403, 'No tienes permisos para ver evaluaciones.');
        }

        // Los filtros de búsqueda y rúbrica no se aplicaban: el botón «Aplicar
        // filtros» recargaba la pantalla sin cambiar nada. Se corrige aquí.
        $search = $request->get('search');
        $rubricaId = $request->get('rubrica_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('proyecto', fn ($p) => $p->where('titulo', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%"))
                    ->orWhereHas('jurado', fn ($j) => $j->where('name', 'like', "%{$search}%"));
            });
        }

        if ($rubricaId) {
            $query->where('rubrica_id', $rubricaId);
        }

        // Resumen sobre TODO lo que el filtro deja ver, no solo la página
        // actual, para que los indicadores no cambien al pasar de página.
        $notas = (clone $query)->pluck('nota_final')->map(fn ($n) => (float) $n);

        $evaluaciones = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // El jurado califica los proyectos que le asignan desde los eventos, y
        // esas notas viven en evento_participantes, no en la tabla evaluaciones.
        // Aquí se reúnen para que pueda repasar todo lo que ya calificó.
        $evaluacionesEventos = null;
        if ($user->hasRole('Jurado')) {
            $evaluacionesEventos = \App\Models\EventoParticipante::where('evaluador_id', $user->id)
                ->where('estado_evaluacion', 'evaluado')
                ->with(['evento:id,titulo', 'usuario:id,name'])
                ->orderByDesc('fecha_evaluacion')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'titulo' => $p->titulo_proyecto ?: 'Proyecto sin título',
                    'estudiante' => $p->usuario?->name,
                    'evento' => $p->evento?->titulo,
                    'nota_final' => $p->nota_final,
                    'fecha_evaluacion' => $p->fecha_evaluacion,
                    'analisis_proyecto' => $p->analisis_proyecto,
                    'fortalezas' => $p->fortalezas,
                    'debilidades' => $p->debilidades,
                    'recomendaciones' => $p->recomendaciones,
                ]);
        }

        // Notas de las propuestas de evento, para sumarlas al resumen del
        // jurado: para él, ambas son simplemente "proyectos que calificó".
        $notasEventos = $evaluacionesEventos?->pluck('nota_final')->map(fn ($n) => (float) $n) ?? collect();
        $notasCombinadas = $notas->concat($notasEventos);

        return Inertia::render('Evaluaciones/Index', [
            'evaluaciones' => $evaluaciones,
            'evaluacionesEventos' => $evaluacionesEventos,
            'resumen' => [
                'total' => $notas->count() + $notasEventos->count(),
                'promedio' => $notasCombinadas->isEmpty() ? null : round($notasCombinadas->avg(), 2),
                'mejor_nota' => $notasCombinadas->isEmpty() ? null : round($notasCombinadas->max(), 2),
                // Sólo tiene sentido para el administrador o el coordinador,
                // que ven evaluaciones de varios jurados a la vez.
                'jurados_distintos' => $user->hasRole(['Administrador', 'Coordinador'])
                    ? (clone $query)->distinct('jurado_id')->count('jurado_id')
                    : null,
            ],
            'rubricas' => Rubrica::select('id', 'nombre')->orderBy('nombre')->get(),
            'filters' => [
                'search' => $search,
                'rubrica_id' => $rubricaId,
            ],
        ]);
    }

    /**
     * Mostrar proyectos asignados a un jurado para evaluar
     */
    public function proyectosAsignados(): Response|RedirectResponse
    {
        $user = auth()->user();

        // Verificar si el usuario está autenticado
        if (!$user) {
            return redirect()->route('login');
        }

        // Verificar roles de manera más flexible
        $userRoles = $user->roles->pluck('name')->toArray();
        $isJurado = in_array('Jurado', $userRoles) || $user->hasRole('Jurado');

        if (!$isJurado) {
            // Si no es jurado, mostrar mensaje informativo en lugar de error 403
            return Inertia::render('Evaluaciones/ProyectosAsignados', [
                'proyectos' => [],
                'rubricas' => [],
                'auth' => [
                    'user' => $user->load('roles')
                ],
                'error' => 'Solo los jurados pueden ver proyectos asignados.'
            ]);
        }

        // Obtener proyectos aprobados que no han sido evaluados por este jurado
        $proyectos = Proyecto::where('estado', 'Aprobado')
            ->whereDoesntHave('evaluaciones', function ($query) use ($user) {
                $query->where('jurado_id', $user->id);
            })
            ->with(['autor', 'semillero'])
            ->get();

        // Obtener rúbricas disponibles
        $rubricas = Rubrica::with('criterios')->get();

        return Inertia::render('Evaluaciones/ProyectosAsignados', [
            'proyectos' => $proyectos,
            'rubricas' => $rubricas,
            'auth' => [
                'user' => $user->load('roles')
            ]
        ]);
    }

    /**
     * Mostrar formulario para crear evaluación
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole('Jurado')) {
            abort(403, 'Solo los jurados pueden crear evaluaciones.');
        }

        $proyectoId = $request->get('proyecto_id');
        $rubricaId = $request->get('rubrica_id');

        if (!$proyectoId || !$rubricaId) {
            return redirect()->route('evaluaciones.proyectos-asignados')
                ->with('error', 'Debe seleccionar un proyecto y una rúbrica.');
        }

        try {
            $proyecto = Proyecto::findOrFail($proyectoId);
            $rubrica = Rubrica::with('criterios')->findOrFail($rubricaId);

            // Verificar que el jurado no haya evaluado ya este proyecto
            $evaluacionExistente = Evaluacion::where('proyecto_id', $proyectoId)
                ->where('jurado_id', $user->id)
                ->first();

            if ($evaluacionExistente) {
                return redirect()->route('evaluaciones.show', $evaluacionExistente)
                    ->with('info', 'Ya has evaluado este proyecto.');
            }

               return Inertia::render('Evaluaciones/Create', [
                   'proyecto' => $proyecto,
                   'rubrica' => $rubrica,
                   'auth' => [
                       'user' => $user->load('roles')
                   ]
               ]);
        } catch (\Exception $e) {
            return redirect()->route('evaluaciones.proyectos-asignados')
                ->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }

    /**
     * Guardar nueva evaluación
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole('Jurado')) {
            abort(403, 'Solo los jurados pueden crear evaluaciones.');
        }

        $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'rubrica_id' => 'required|exists:rubricas,id',
            'observaciones' => 'nullable|string',
            'puntajes' => 'required|array',
            'puntajes.*' => 'required|integer|min:1',
        ]);

        $proyectoId = $request->proyecto_id;
        $rubricaId = $request->rubrica_id;

        // Verificar que el jurado no haya evaluado ya este proyecto
        $evaluacionExistente = Evaluacion::where('proyecto_id', $proyectoId)
            ->where('jurado_id', $user->id)
            ->first();

        if ($evaluacionExistente) {
            return back()->withErrors([
                'proyecto' => 'Ya has evaluado este proyecto.'
            ]);
        }

        DB::transaction(function () use ($request, $user, $proyectoId, $rubricaId) {
            // Crear la evaluación
            $evaluacion = Evaluacion::create([
                'proyecto_id' => $proyectoId,
                'jurado_id' => $user->id,
                'rubrica_id' => $rubricaId,
                'observaciones' => $request->observaciones,
                'estado' => 'Pendiente',
            ]);

            // Crear los detalles de evaluación
            foreach ($request->puntajes as $criterioId => $puntaje) {
                $criterio = CriterioRubrica::findOrFail($criterioId);

                // El criterio debe pertenecer a la rúbrica elegida: si el
                // jurado cambió de rúbrica en el formulario y quedó algún
                // puntaje de la anterior, no debe colarse en esta evaluación.
                if ($criterio->rubrica_id !== (int) $rubricaId) {
                    throw new \Exception("El criterio '{$criterio->criterio}' no pertenece a la rúbrica seleccionada.");
                }

                // Verificar que el puntaje esté dentro del rango válido
                if ($puntaje < 1 || $puntaje > $criterio->puntaje_maximo) {
                    throw new \Exception("El puntaje para '{$criterio->criterio}' debe estar entre 1 y {$criterio->puntaje_maximo}.");
                }

                DetalleEvaluacion::create([
                    'evaluacion_id' => $evaluacion->id,
                    'criterio_id' => $criterioId,
                    'puntaje' => $puntaje,
                ]);
            }

            // Calcular y guardar la nota final
            $evaluacion->completarEvaluacion();
        });

        return redirect()->route('evaluaciones.index')
            ->with('success', 'Evaluación registrada exitosamente.');
    }

    /**
     * Mostrar evaluación específica
     */
    public function show(Evaluacion $evaluacion): Response
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $evaluacion->jurado_id !== $user->id) {
            abort(403, 'No tienes permisos para ver esta evaluación.');
        }

        $evaluacion->load([
            'proyecto.autor',
            'eventoParticipante.evento',
            'eventoParticipante.usuario',
            'jurado',
            'rubrica.criterios',
            'detalleEvaluaciones.criterio'
        ]);

        return Inertia::render('Evaluaciones/Show', [
            'evaluacion' => $evaluacion,
        ]);
    }

    /**
     * Obtener resultados de un proyecto específico
     */
    public function projectResults($proyectoId)
    {
        $proyecto = Proyecto::with([
            'evaluaciones.jurado',
            'evaluaciones.detalleEvaluaciones.criterio',
            'autor',
            'semillero'
        ])->findOrFail($proyectoId);

        $evaluacionesCompletas = $proyecto->evaluaciones()
            ->where('estado', 'Evaluado')
            ->get();

        $promedio = $proyecto->promedio_evaluaciones;
        $totalEvaluaciones = $proyecto->total_evaluaciones;

        // Calcular estadísticas por criterio
        $estadisticasCriterios = [];
        if ($evaluacionesCompletas->isNotEmpty()) {
            $criterios = $proyecto->evaluaciones->first()->rubrica->criterios;

            foreach ($criterios as $criterio) {
                $puntajes = [];
                foreach ($evaluacionesCompletas as $evaluacion) {
                    $detalle = $evaluacion->detalleEvaluaciones
                        ->where('criterio_id', $criterio->id)
                        ->first();

                    if ($detalle) {
                        $puntajes[] = $detalle->puntaje;
                    }
                }

                $estadisticasCriterios[] = [
                    'criterio' => $criterio->criterio,
                    'peso' => $criterio->peso,
                    'puntaje_promedio' => count($puntajes) > 0 ? round(array_sum($puntajes) / count($puntajes), 2) : 0,
                    'puntaje_maximo' => $criterio->puntaje_maximo,
                    'total_evaluaciones' => count($puntajes),
                ];
            }
        }

        return response()->json([
            'proyecto' => $proyecto,
            'promedio' => $promedio,
            'total_evaluaciones' => $totalEvaluaciones,
            'estadisticas_criterios' => $estadisticasCriterios,
            'evaluaciones' => $evaluacionesCompletas,
        ]);
    }

    /**
     * Obtener evaluaciones de un jurado específico
     */
    public function juradoEvaluations($juradoId)
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $user->id != $juradoId) {
            abort(403, 'No tienes permisos para ver estas evaluaciones.');
        }

        $evaluaciones = Evaluacion::where('jurado_id', $juradoId)
            ->with(['proyecto.autor', 'rubrica'])
            ->orderBy('created_at', 'desc')
            ->get();

        $jurado = \App\Models\User::findOrFail($juradoId);

        return response()->json([
            'jurado' => $jurado,
            'evaluaciones' => $evaluaciones,
            'total_evaluaciones' => $evaluaciones->count(),
            'promedio_notas' => $evaluaciones->where('estado', 'Evaluado')->avg('nota_final'),
        ]);
    }

    /**
     * Obtener ranking de proyectos
     */
    public function ranking()
    {
        // No existe una EvaluacionPolicy, así que authorize() denegaba esta
        // pantalla a todos los roles. Se controla por rol, igual que index().
        if (! auth()->user()->hasRole(['Administrador', 'Coordinador', 'Docente/Tutor'])) {
            abort(403, 'No tienes permisos para consultar el ranking de proyectos.');
        }

        $proyectos = Proyecto::where('estado', 'Aprobado')
            ->with(['autor', 'semillero'])
            ->get()
            ->map(function ($proyecto) {
                return [
                    'id' => $proyecto->id,
                    'titulo' => $proyecto->titulo,
                    'codigo' => $proyecto->codigo,
                    'autor' => $proyecto->autor->name,
                    'semillero' => $proyecto->semillero->nombre,
                    'promedio' => $proyecto->promedio_evaluaciones,
                    'total_evaluaciones' => $proyecto->total_evaluaciones,
                    'evaluado' => $proyecto->evaluado,
                ];
            })
            ->filter(function ($proyecto) {
                return $proyecto['evaluado']; // Solo proyectos evaluados
            })
            ->sortByDesc('promedio')
            ->values();

        return response()->json([
            'ranking' => $proyectos,
            'total_proyectos' => $proyectos->count(),
        ]);
    }
}
