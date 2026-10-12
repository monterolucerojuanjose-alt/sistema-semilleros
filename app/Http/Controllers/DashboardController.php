<?php

namespace App\Http\Controllers;

use App\Models\Semillero;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\InscripcionEvento;
use App\Models\Evaluacion;
use App\Models\Certificado;
use App\Models\Rubrica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $user->load('roles');

        return Inertia::render('Dashboard', [
            'auth' => [
                'user' => $user,
            ],
            'stats' => $this->getDashboardStats($user),
            // Trabajo pendiente del jurado, para que entre y sepa qué le falta.
            'pendientesJurado' => $user->hasRole('Jurado')
                ? \App\Models\EventoParticipante::where('evaluador_id', $user->id)
                    ->where('estado_evaluacion', '!=', 'evaluado')
                    ->with(['evento:id,titulo', 'usuario:id,name'])
                    ->orderBy('created_at')
                    ->limit(5)
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'titulo' => $p->titulo_proyecto ?: 'Proyecto sin título',
                        'estudiante' => $p->usuario?->name,
                        'evento' => $p->evento?->titulo,
                        'aula' => $p->aula,
                        'horario' => $p->horario,
                    ])
                : null,
            // Contenido real para que ningún rol entre a una pantalla vacía.
            'proximosEventos' => $this->proximosEventos(),
            'actividadReciente' => $this->actividadReciente($user),
            'ultimasNotificaciones' => $this->ultimasNotificaciones($user),
        ]);
    }

    /**
     * Eventos académicos que están por comenzar.
     */
    private function proximosEventos(): array
    {
        return \App\Models\Evento::where('estado', 'activo')
            ->where('fecha_inicio', '>=', now())
            ->orderBy('fecha_inicio')
            ->limit(4)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'titulo' => $e->titulo,
                'ubicacion' => $e->ubicacion,
                'fecha_inicio' => $e->fecha_inicio,
                'dias_restantes' => max(0, (int) now()->startOfDay()->diffInDays($e->fecha_inicio->startOfDay(), false)),
            ])
            ->all();
    }

    /**
     * Últimos movimientos que el usuario puede ver según su rol.
     */
    private function actividadReciente($user): array
    {
        // Administradores y coordinadores ven el movimiento de todo el sistema.
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            return \App\Models\Auditoria::with('usuario:id,name')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'texto' => $a->detalle ?: ucfirst((string) $a->accion),
                    'autor' => $a->usuario?->name ?? 'Sistema',
                    'fecha' => $a->created_at,
                ])
                ->all();
        }

        // Los demás roles ven el avance de los proyectos que les competen.
        $query = Proyecto::with(['autor:id,name', 'semillero:id,nombre'])->latest();

        if ($user->hasRole('Docente/Tutor')) {
            $query->whereHas('semillero', fn ($q) => $q->where('tutor_id', $user->id));
        } elseif ($user->hasRole('Estudiante')) {
            $query->where('autor_id', $user->id);
        }

        return $query->limit(6)->get()->map(fn ($p) => [
            'id' => $p->id,
            'texto' => $p->titulo,
            'autor' => $p->autor?->name,
            'contexto' => $p->semillero?->nombre,
            'estado' => $p->estado,
            'fecha' => $p->created_at,
        ])->all();
    }

    /**
     * Avisos sin leer, para que se vean apenas entra.
     */
    private function ultimasNotificaciones($user): array
    {
        return \App\Models\Notificacion::where('usuario_id', $user->id)
            ->where('leida', false)
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'titulo' => $n->titulo,
                'mensaje' => $n->mensaje,
                'tipo' => $n->tipo,
                'fecha' => $n->created_at,
            ])
            ->all();
    }

    /**
     * Obtener resumen general del sistema
     */
    public function getResumenGeneral(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $resumen = [
            'totalUsuarios' => User::count(),
            'totalProyectos' => Proyecto::count(),
            'totalSemilleros' => Semillero::count(),
            'totalInscripciones' => InscripcionEvento::count(),
            'totalEvaluaciones' => Evaluacion::count(),
            'totalCertificados' => Certificado::count(),
            'proyectosAprobados' => Proyecto::where('estado', 'Aprobado')->count(),
            'proyectosPendientes' => Proyecto::whereIn('estado', ['Inscrito', 'En revisión'])->count(),
            'proyectosRechazados' => Proyecto::where('estado', 'Rechazado')->count(),
            'semillerosActivos' => Semillero::where('estado', 'activo')->count(),
        ];

        return response()->json($resumen);
    }

    /**
     * Obtener proyectos por estado
     */
    public function getProyectosPorEstado(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $proyectos = Proyecto::selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->estado => $item->cantidad];
            });

        return response()->json($proyectos);
    }

    /**
     * Obtener proyectos por semillero
     */
    public function getProyectosPorSemillero(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $proyectos = Proyecto::join('semilleros', 'proyectos.semillero_id', '=', 'semilleros.id')
            ->selectRaw('semilleros.nombre, COUNT(*) as cantidad')
            ->groupBy('semilleros.nombre')
            ->orderBy('cantidad', 'desc')
            ->get();

        return response()->json($proyectos);
    }

    /**
     * Obtener ranking de proyectos de eventos evaluados por jurados
     */
    public function getRankingProyectos(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        // Obtener proyectos de eventos evaluados por jurados
        $ranking = \App\Models\EventoParticipante::with(['usuario', 'evento'])
            ->whereNotNull('evaluador_id')
            ->where('estado_evaluacion', 'evaluado')
            ->whereNotNull('nota_final')
            ->orderBy('nota_final', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($participante, $index) {
                return [
                    'posicion' => $index + 1,
                    'titulo' => $participante->titulo_proyecto ?: 'Proyecto de Evento',
                    'autor' => $participante->usuario->name,
                    'semillero' => $participante->evento->titulo, // Usar el nombre del evento como "semillero"
                    'nota_promedio' => round($participante->nota_final, 2),
                    'total_evaluaciones' => 1, // Cada participante tiene una evaluación
                ];
            });

        return response()->json($ranking);
    }

    /**
     * Obtener participación por rol
     */
    public function getParticipacionPorRol(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $participacion = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->selectRaw('roles.name as rol, COUNT(*) as cantidad')
            ->groupBy('roles.name')
            ->get();

        return response()->json($participacion);
    }

    /**
     * Obtener estadísticas de evaluaciones por rúbrica
     */
    public function getEvaluacionesPorRubrica(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $evaluaciones = Evaluacion::join('rubricas', 'evaluaciones.rubrica_id', '=', 'rubricas.id')
            ->selectRaw('rubricas.nombre, AVG(evaluaciones.nota_final) as promedio, COUNT(*) as total')
            ->groupBy('rubricas.id', 'rubricas.nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'rubrica' => $item->nombre,
                    'promedio' => round($item->promedio, 2),
                    'total' => $item->total,
                ];
            });

        return response()->json($evaluaciones);
    }

    /**
     * Obtener inscripciones por tipo
     */
    public function getInscripcionesPorTipo(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $inscripciones = InscripcionEvento::selectRaw('tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tipo => $item->cantidad];
            });

        return response()->json($inscripciones);
    }

    /**
     * Obtener estadísticas mensuales
     */
    public function getEstadisticasMensuales(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a estas estadísticas.');
        }

        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $meses[] = [
                'mes' => $fecha->format('Y-m'),
                'nombre' => $fecha->format('M Y'),
                'proyectos' => Proyecto::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)
                    ->count(),
                'inscripciones' => InscripcionEvento::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)
                    ->count(),
                'evaluaciones' => Evaluacion::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)
                    ->count(),
            ];
        }

        return response()->json($meses);
    }

    private function getDashboardStats($user)
    {
        $role = $user->roles->first()?->name ?? 'Estudiante';

        switch ($role) {
            case 'Administrador':
                return [
                    'totalUsuarios' => User::count(),
                    'totalProyectos' => Proyecto::count(),
                    'totalSemilleros' => Semillero::count(),
                    'totalCertificados' => Certificado::count(),
                    'semillerosActivos' => Semillero::where('estado', 'activo')->count(),
                    'proyectosAprobados' => Proyecto::where('estado', 'Aprobado')->count(),
                    'totalEvaluaciones' => Evaluacion::count(),
                    'totalInscripciones' => InscripcionEvento::count(),
                ];

            case 'Coordinador':
                return [
                    'totalUsuarios' => User::count(),
                    'totalProyectos' => Proyecto::count(),
                    'totalSemilleros' => Semillero::count(),
                    'totalCertificados' => Certificado::count(),
                    'semillerosActivos' => Semillero::where('estado', 'activo')->count(),
                    'proyectosAprobados' => Proyecto::where('estado', 'Aprobado')->count(),
                    'totalEvaluaciones' => Evaluacion::count(),
                    'totalInscripciones' => InscripcionEvento::count(),
                ];

            case 'Docente/Tutor':
                return [
                    'misSemilleros' => Semillero::where('tutor_id', $user->id)->count(),
                    'semillerosActivos' => Semillero::where('tutor_id', $user->id)->where('estado', 'activo')->count(),
                    'estudiantesAsignados' => Semillero::where('tutor_id', $user->id)
                        ->withCount('miembrosAprobados')
                        ->get()
                        ->sum('miembros_aprobados_count'),
                    'misProyectos' => Proyecto::whereHas('semillero', function($query) use ($user) {
                        $query->where('tutor_id', $user->id);
                    })->count(),
                    // Estudiantes que esperan que el tutor apruebe su ingreso.
                    'solicitudesPendientes' => \Illuminate\Support\Facades\DB::table('semillero_user')
                        ->join('semilleros', 'semillero_user.semillero_id', '=', 'semilleros.id')
                        ->where('semilleros.tutor_id', $user->id)
                        ->where('semillero_user.estado_solicitud', 'pendiente')
                        ->count(),
                ];

            case 'Estudiante':
                return [
                    'misProyectos' => Proyecto::where('autor_id', $user->id)->count(),
                    'inscripcionesActivas' => $user->semillerosAprobados()->count(),
                    'solicitudesPendientes' => $user->solicitudesPendientes()->count(),
                    'semillerosDisponibles' => Semillero::where('estado', 'activo')->count(),
                    'misCertificados' => Certificado::where('usuario_id', $user->id)->count(),
                ];

            case 'Jurado':
                // Los proyectos que evalúa un jurado son los que le asignan
                // desde los eventos; el rol no emite certificados, así que ese
                // indicador no aplica aquí.
                $asignados = \App\Models\EventoParticipante::where('evaluador_id', $user->id);

                $notasPropias = (clone $asignados)
                    ->where('estado_evaluacion', 'evaluado')
                    ->whereNotNull('nota_final');

                return [
                    'evaluacionesPendientes' => (clone $asignados)
                        ->where('estado_evaluacion', '!=', 'evaluado')
                        ->count(),
                    'proyectosEvaluados' => (clone $notasPropias)->count(),
                    'calificacionPromedio' => round((clone $notasPropias)->avg('nota_final') ?? 0, 2),
                    'totalAsignados' => (clone $asignados)->count(),
                ];

            default:
                return [];
        }
    }
}
