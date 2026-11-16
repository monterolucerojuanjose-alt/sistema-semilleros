<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventoParticipante;
use App\Models\Evento;
use App\Models\User;
use Inertia\Inertia;

class ResultadosEvaluacionController extends Controller
{
    /** Valores de evento_participantes.estado_evaluacion y su nombre visible. */
    private const ESTADOS_EVALUACION = [
        'sin_evaluar' => 'Sin evaluar',
        'en_revision' => 'En revisión',
        'evaluado' => 'Evaluado',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->roles->first()->name ?? $user->role;

        // Filtros
        $search = $request->get('search', '');
        $eventoId = $request->get('evento_id', '');
        $estadoEvaluacion = array_key_exists($request->get('estado_evaluacion', ''), self::ESTADOS_EVALUACION)
            ? $request->get('estado_evaluacion')
            : '';

        // El orden llega por la URL: se acepta sólo de una lista conocida. Antes
        // cualquier valor iba directo a la consulta y uno inválido daba error 500.
        $sortBy = in_array($request->get('sort_by'), ['fecha_evaluacion', 'nota_final', 'estado_evaluacion', 'created_at'], true)
            ? $request->get('sort_by')
            : 'fecha_evaluacion';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';

        // Universo que puede ver el usuario según su rol. Las estadísticas se
        // calculan sobre este mismo universo: antes un tutor veía en la tabla
        // sólo a sus estudiantes, pero los totales de todo el sistema.
        $base = EventoParticipante::query()->whereNotNull('evaluador_id');

        if ($userRole === 'Docente/Tutor') {
            // Los docentes sólo ven evaluaciones de sus estudiantes. El vínculo
            // no es una columna docente_id (no existe en users): un estudiante
            // es "suyo" si está aprobado en alguno de sus semilleros.
            $base->whereHas('usuario.semillerosAprobados', function ($q) use ($user) {
                $q->where('semilleros.tutor_id', $user->id);
            });
        }

        $query = (clone $base)->with(['evento:id,titulo,fecha_inicio', 'usuario', 'evaluador:id,name']);

        if ($search) {
            $query->whereHas('usuario', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($eventoId) {
            $query->where('evento_id', $eventoId);
        }

        if ($estadoEvaluacion) {
            $query->where('estado_evaluacion', $estadoEvaluacion);
        }

        // Las aún no evaluadas no tienen fecha: se envían al final.
        $resultados = $query
            ->orderByRaw("{$sortBy} IS NULL")
            ->orderBy($sortBy, $sortOrder)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $promedio = (clone $base)->where('estado_evaluacion', 'evaluado')->avg('nota_final');

        $estadisticas = [
            'total_evaluaciones' => (clone $base)->count(),
            'evaluaciones_completadas' => (clone $base)->where('estado_evaluacion', 'evaluado')->count(),
            // «En revisión» también está pendiente: antes sólo se contaba «sin evaluar».
            'evaluaciones_pendientes' => (clone $base)->whereIn('estado_evaluacion', ['sin_evaluar', 'en_revision'])->count(),
            'promedio_general' => $promedio !== null ? round((float) $promedio, 2) : null,
        ];

        // Lista de eventos para el filtro
        $eventos = Evento::select('id', 'titulo', 'fecha_inicio', 'fecha_fin')
            ->whereHas('participantes', function($q) {
                $q->whereNotNull('evaluador_id');
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Los valores reales de la columna. Antes se ofrecía «Rechazado», que
        // la columna no admite, y faltaba «En revisión».
        $estadosEvaluacion = self::ESTADOS_EVALUACION;

        return Inertia::render('ResultadosEvaluacion/Index', [
            'resultados' => $resultados,
            'estadisticas' => $estadisticas,
            'eventos' => $eventos,
            'estadosEvaluacion' => $estadosEvaluacion,
            'filters' => [
                'search' => $search,
                'evento_id' => $eventoId,
                'estado_evaluacion' => $estadoEvaluacion,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
    }

    public function show($id)
    {
        $resultado = EventoParticipante::with([
            'evento',
            'usuario',
            'evaluador'
        ])->findOrFail($id);

        // Verificar permisos
        $user = auth()->user();
        $userRole = $user->roles->first()->name ?? $user->role;

        if ($userRole === 'Docente/Tutor') {
            // El estudiante es "suyo" si está aprobado en uno de sus semilleros.
            $esSuEstudiante = $resultado->usuario
                ?->semillerosAprobados()
                ->where('semilleros.tutor_id', $user->id)
                ->exists();

            if (! $esSuEstudiante) {
                abort(403, 'No tienes permisos para ver esta evaluación.');
            }
        }

        $evaluacion = \App\Models\Evaluacion::where('evento_participante_id', $resultado->id)
            ->with('jurado:id,name')
            ->latest('id')
            ->first();

        return Inertia::render('ResultadosEvaluacion/Show', [
            'resultado' => $resultado,
            // Puntaje por criterio de la rúbrica con la que calificó el jurado.
            'rubrica' => $evaluacion?->desglosePorCriterio(),
            'observacionesRubrica' => $evaluacion?->observaciones,
            'archivoUrl' => $resultado->archivo_proyecto ? asset('storage/' . $resultado->archivo_proyecto) : null,
        ]);
    }

    public function estadisticasGenerales()
    {
        $estadisticas = [
            'por_rol' => EventoParticipante::whereNotNull('evaluador_id')
                ->where('estado_evaluacion', 'evaluado')
                ->join('users', 'evento_participantes.user_id', '=', 'users.id')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->selectRaw('roles.name as rol, COUNT(*) as total, AVG(nota_final) as promedio')
                ->groupBy('roles.name')
                ->get(),

            'por_evento' => EventoParticipante::whereNotNull('evaluador_id')
                ->where('estado_evaluacion', 'evaluado')
                ->join('eventos', 'evento_participantes.evento_id', '=', 'eventos.id')
                ->selectRaw('eventos.titulo as evento, COUNT(*) as total, AVG(nota_final) as promedio')
                ->groupBy('eventos.id', 'eventos.titulo')
                ->orderBy('promedio', 'desc')
                ->get(),

            'por_evaluador' => EventoParticipante::whereNotNull('evaluador_id')
                ->where('estado_evaluacion', 'evaluado')
                ->join('users', 'evento_participantes.evaluador_id', '=', 'users.id')
                ->selectRaw('users.name as evaluador, COUNT(*) as total, AVG(nota_final) as promedio')
                ->groupBy('users.id', 'users.name')
                ->orderBy('promedio', 'desc')
                ->get()
        ];

        return response()->json($estadisticas);
    }
}
