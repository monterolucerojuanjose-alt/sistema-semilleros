<?php

namespace App\Http\Controllers;

use App\Models\InscripcionEvento;
use App\Models\Proyecto;
use App\Jobs\EnviarNotificacionInscripcion;
use App\Jobs\EnviarNotificacionEstadoInscripcion;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Barryvdh\DomPDF\Facade\Pdf;

class InscripcionEventoController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de inscripciones
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Obtener filtros de la request
        $filtros = $request->only([
            'buscar', 'estado', 'tipo', 'fecha_inicio', 'fecha_fin', 'semillero_id'
        ]);

        // Construir query base
        $query = InscripcionEvento::with(['proyecto.autor', 'proyecto.semillero']);

        // Aplicar filtros según el rol del usuario
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            // Administradores y coordinadores ven todas las inscripciones
            $query->buscar($filtros['buscar'] ?? '')
                  ->porEstado($filtros['estado'] ?? '')
                  ->porTipo($filtros['tipo'] ?? '')
                  ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
                  ->porSemillero($filtros['semillero_id'] ?? '');
        } elseif ($user->hasRole('Docente/Tutor')) {
            // Docentes ven inscripciones de proyectos de sus semilleros
            $semilleroIds = $user->semillerosComoTutor()->pluck('id');
            $query->whereHas('proyecto', function($q) use ($semilleroIds) {
                    $q->whereIn('semillero_id', $semilleroIds);
                })
                ->buscar($filtros['buscar'] ?? '')
                ->porEstado($filtros['estado'] ?? '')
                ->porTipo($filtros['tipo'] ?? '')
                ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);
        } else {
            // Estudiantes ven solo sus inscripciones
            $query->porUsuario($user->id)
                  ->buscar($filtros['buscar'] ?? '')
                  ->porEstado($filtros['estado'] ?? '')
                  ->porTipo($filtros['tipo'] ?? '')
                  ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);
        }

        // Ordenar y paginar
        $inscripciones = $query->orderBy('created_at', 'desc')->paginate(15);

        // NUEVO: Obtener propuestas de eventos del estudiante
        $propuestasEventosAprobadas = [];
        $propuestasPendientesRechazadas = [];
        if ($user->hasRole('Estudiante')) {
            $propuestasEventosAprobadas = \App\Models\EventoParticipante::where('user_id', $user->id)
                ->where('estado', 'aprobado')
                ->with(['evento', 'revisor', 'evaluador'])
                ->orderBy('created_at', 'desc')
                ->get();

            $propuestasPendientesRechazadas = \App\Models\EventoParticipante::where('user_id', $user->id)
                ->whereIn('estado', ['pendiente', 'rechazado'])
                ->with(['evento', 'revisor', 'evaluador'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Resumen del usuario, calculado sin los filtros activos para que los
        // indicadores muestren siempre el total y no varíen al filtrar.
        $resumen = null;
        if ($user->hasRole('Estudiante')) {
            $inscripcionesUsuario = InscripcionEvento::porUsuario($user->id)
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado');

            // Sólo los estados que el listado muestra; de lo contrario el total
            // del resumen no coincidiría con las filas visibles.
            $propuestasUsuario = \App\Models\EventoParticipante::where('user_id', $user->id)
                ->whereIn('estado', ['aprobado', 'pendiente', 'rechazado'])
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado');

            $resumen = [
                'total' => $inscripcionesUsuario->sum() + $propuestasUsuario->sum(),
                'en_revision' => ($inscripcionesUsuario['En revisión'] ?? 0) + ($propuestasUsuario['pendiente'] ?? 0),
                'aceptadas' => ($inscripcionesUsuario['Aceptado'] ?? 0)
                    + ($inscripcionesUsuario['Aprobado'] ?? 0)
                    + ($propuestasUsuario['aprobado'] ?? 0),
                'rechazadas' => ($inscripcionesUsuario['Rechazado'] ?? 0) + ($propuestasUsuario['rechazado'] ?? 0),
            ];
        }

        // Obtener datos adicionales para los filtros
        $semilleros = \App\Models\Semillero::select('id', 'nombre')->get();
        $estados = ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado', 'Aprobado'];
        $tipos = ['Ponencia', 'Poster', 'Proyecto de Evento'];

        // Determinar qué vista usar según la ruta
        $routeName = request()->route() ? request()->route()->getName() : 'inscripciones.index';
        $viewName = $routeName === 'mis-inscripciones' ? 'Inscripciones/MisInscripciones' : 'Inscripciones/Index';

        return Inertia::render($viewName, [
            'inscripciones' => $inscripciones,
            'propuestasEventosAprobadas' => $propuestasEventosAprobadas, // NUEVO
            'propuestasPendientesRechazadas' => $propuestasPendientesRechazadas, // NUEVO
            'resumen' => $resumen,
            'semilleros' => $semilleros,
            'estados' => $estados,
            'tipos' => $tipos,
            'filtros' => $filtros,
        ]);
    }

    // MÉTODOS create() y store() ELIMINADOS - Los estudiantes ya no pueden crear inscripciones tradicionales

    /**
     * Mostrar detalle de inscripción
     */
    public function show(InscripcionEvento $inscripcion): Response
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($user->hasRole('Docente/Tutor')) {
                $esTutor = $user->semillerosComoTutor()->where('id', $inscripcion->proyecto->semillero_id)->exists();
                if (!$esTutor) {
                    abort(403, 'No tienes permisos para ver esta inscripción.');
                }
            } else {
                if ($inscripcion->proyecto->autor_id !== $user->id) {
                    abort(403, 'No tienes permisos para ver esta inscripción.');
                }
            }
        }

        $inscripcion->load(['proyecto.autor', 'proyecto.semillero']);

        return Inertia::render('Inscripciones/Show', [
            'inscripcion' => $inscripcion,
            'auth' => [
                'user' => $user->load('roles')
            ]
        ]);
    }

    /**
     * Actualizar estado de inscripción
     */
    public function update(Request $request, InscripcionEvento $inscripcion): RedirectResponse
    {
        $user = auth()->user();

        // Solo coordinadores y administradores pueden actualizar
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para actualizar inscripciones.');
        }

        $request->validate([
            'estado' => 'required|in:En revisión,Aceptado,Rechazado',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        // Si se cambia a Aceptado o Rechazado, las observaciones son obligatorias
        if (in_array($request->estado, ['Aceptado', 'Rechazado']) && empty($request->observaciones)) {
            return redirect()->back()
                ->withErrors(['observaciones' => 'Las observaciones son obligatorias al aprobar o rechazar.']);
        }

        $estadoAnterior = $inscripcion->estado;

        $inscripcion->update([
            'estado' => $request->estado,
            'observaciones' => $request->observaciones,
        ]);

        // Enviar notificación por correo si cambió el estado
        if ($estadoAnterior !== $request->estado) {
            EnviarNotificacionEstadoInscripcion::dispatch($inscripcion, $estadoAnterior);
        }

        $mensaje = match($request->estado) {
            'En revisión' => 'Inscripción marcada como en revisión.',
            'Aceptado' => 'Inscripción aceptada exitosamente.',
            'Rechazado' => 'Inscripción rechazada.',
        };

        return redirect()->back()->with('success', $mensaje);
    }

    /**
     * Cancelar inscripción
     */
    public function destroy(InscripcionEvento $inscripcion): RedirectResponse
    {
        $user = auth()->user();

        // Solo el autor del proyecto o administradores pueden cancelar
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($inscripcion->proyecto->autor_id !== $user->id) {
                abort(403, 'No tienes permisos para cancelar esta inscripción.');
            }
        }

        // Solo se pueden cancelar inscripciones en estado "Inscrito" o "En revisión"
        if (!in_array($inscripcion->estado, ['Inscrito', 'En revisión'])) {
            return redirect()->back()
                ->withErrors(['error' => 'No se puede cancelar una inscripción que ya fue evaluada.']);
        }

        $inscripcion->delete();

        return redirect()->route('inscripciones.index')
            ->with('success', 'Inscripción cancelada exitosamente.');
    }

    /**
     * Generar comprobante PDF de inscripción
     */
    public function comprobante(InscripcionEvento $inscripcion)
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($inscripcion->proyecto->autor_id !== $user->id) {
                abort(403, 'No tienes permisos para descargar este comprobante.');
            }
        }

        $inscripcion->load(['proyecto.autor', 'proyecto.semillero']);

        // Generar PDF
        $pdf = Pdf::loadView('inscripciones.comprobante', [
            'inscripcion' => $inscripcion,
            'fecha_generacion' => now()->format('d/m/Y H:i:s')
        ]);

        $nombreArchivo = 'Comprobante_Inscripcion_' . $inscripcion->codigo_inscripcion . '.pdf';

        return $pdf->download($nombreArchivo);
    }
}
