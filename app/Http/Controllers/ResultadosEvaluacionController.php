<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventoParticipante;
use App\Models\Evento;
use App\Models\User;
use Inertia\Inertia;

class ResultadosEvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->roles->first()->name ?? $user->role;

        // Filtros
        $search = $request->get('search', '');
        $eventoId = $request->get('evento_id', '');
        $estadoEvaluacion = $request->get('estado_evaluacion', '');
        $sortBy = $request->get('sort_by', 'fecha_evaluacion');
        $sortOrder = $request->get('sort_order', 'desc');

        // Construir query base
        $query = EventoParticipante::with([
            'evento',
            'usuario',
            'evaluador'
        ])->whereNotNull('evaluador_id');

        // Aplicar filtros según el rol
        if ($userRole === 'Docente/Tutor') {
            // Los docentes sólo ven evaluaciones de sus estudiantes. El vínculo
            // no es una columna docente_id (no existe en users): un estudiante
            // es "suyo" si está aprobado en alguno de sus semilleros.
            $query->whereHas('usuario.semillerosAprobados', function ($q) use ($user) {
                $q->where('semilleros.tutor_id', $user->id);
            });
        }

        // Filtro por búsqueda
        if ($search) {
            $query->whereHas('usuario', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por evento
        if ($eventoId) {
            $query->where('evento_id', $eventoId);
        }

        // Filtro por estado de evaluación
        if ($estadoEvaluacion) {
            $query->where('estado_evaluacion', $estadoEvaluacion);
        }

        // Ordenamiento
        $query->orderBy($sortBy, $sortOrder);

        $resultados = $query->paginate(15);

        // Estadísticas generales
        $promedio = EventoParticipante::whereNotNull('evaluador_id')
            ->where('estado_evaluacion', 'evaluado')
            ->avg('nota_final');

        $estadisticas = [
            'total_evaluaciones' => EventoParticipante::whereNotNull('evaluador_id')->count(),
            'evaluaciones_completadas' => EventoParticipante::whereNotNull('evaluador_id')
                ->where('estado_evaluacion', 'evaluado')->count(),
            'evaluaciones_pendientes' => EventoParticipante::whereNotNull('evaluador_id')
                ->where('estado_evaluacion', 'sin_evaluar')->count(),
            'promedio_general' => $promedio ? (float)$promedio : 0.0,
        ];

        // Lista de eventos para el filtro
        $eventos = Evento::select('id', 'titulo', 'fecha_inicio', 'fecha_fin')
            ->whereHas('participantes', function($q) {
                $q->whereNotNull('evaluador_id');
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Estados de evaluación disponibles
        $estadosEvaluacion = [
            'sin_evaluar' => 'Sin Evaluar',
            'evaluado' => 'Evaluado',
            'rechazado' => 'Rechazado'
        ];

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

        return Inertia::render('ResultadosEvaluacion/Show', [
            'resultado' => $resultado
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
