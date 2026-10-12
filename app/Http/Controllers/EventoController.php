<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoRequest;
use App\Http\Requests\UpdateEventoRequest;
use App\Models\CriterioRubrica;
use App\Models\DetalleEvaluacion;
use App\Models\Evaluacion;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Rubrica;
use App\Models\User;
use App\Support\CatalogosEvento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): Response
    {
        $user = auth()->user();

        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            $eventos = Evento::with(['creador', 'participantes.usuario'])
                ->withCount('participantes')
                ->orderBy('fecha_inicio', 'desc')
                ->get();

            $eventos->each(function ($evento) {
                $evento->estado_dinamico = $evento->estado_dinamico;
            });
        } else {
            $eventos = Evento::activos()
                ->with([
                    'creador',
                    'participantes' => fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->latest('created_at'),
                ])
                ->withCount('participantes')
                ->orderBy('fecha_inicio', 'asc')
                ->get();

            $eventos->each(function ($evento) {
                $participacion = $evento->participantes->first();
                $estaInscritoActivamente = $participacion
                    && in_array($participacion->estado, ['pendiente', 'aprobado', 'asistio'], true);

                $evento->esta_inscrito = $estaInscritoActivamente;
                $evento->estado_inscripcion = $participacion?->estado;
                $evento->fue_rechazado = $participacion?->estado === 'rechazado';
                $evento->estado_dinamico = $evento->estado_dinamico;
                $evento->cupos_disponibles = $evento->cupos_disponibles;
                // Con esto la pantalla puede mostrar el motivo del rechazo y
                // prellenar el formulario cuando el estudiante corrige y reenvía.
                $evento->propuesta_rechazada = $evento->fue_rechazado
                    ? $this->propuestaRechazadaPayload($participacion)
                    : null;
            });

            // Los que aún admiten propuestas van primero, del más próximo al
            // más lejano; detrás, los cerrados del más reciente al más antiguo.
            // Ordenar solo por fecha dejaba los eventos ya terminados arriba y
            // el único abierto al final de la lista, donde no se veía.
            $eventos = $eventos->sortBy(fn ($evento) => [
                $evento->estado_dinamico === 'activo' ? 0 : 1,
                $evento->estado_dinamico === 'activo'
                    ? $evento->fecha_inicio->timestamp
                    : -$evento->fecha_inicio->timestamp,
            ])->values();
        }

        return Inertia::render('Eventos/Index', [
            'eventos' => $eventos,
            // Cuántos admiten propuestas ahora mismo: la pantalla lo usa para
            // explicar al estudiante por qué no ve ningún botón de envío.
            'eventosAbiertos' => $user->hasRole(['Administrador', 'Coordinador'])
                ? null
                : $eventos->where('estado_dinamico', 'activo')->count(),
            'modalidades' => CatalogosEvento::MODALIDADES,
            'areasParticipacion' => CatalogosEvento::AREAS_PARTICIPACION,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para crear eventos.');
        }

        return Inertia::render('Eventos/Create', [
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function store(StoreEventoRequest $request): RedirectResponse
    {
        $validated = $this->normalizeEventoData($request->validated());
        $validated['creado_por'] = auth()->id();

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('eventos', 'public');
        }

        Evento::create($validated);

        return redirect()->route('eventos.index')
            ->with('success', 'Evento creado exitosamente.');
    }

    public function show(Evento $evento): Response
    {
        $user = auth()->user();
        $evento->load(['creador', 'participantes.usuario']);
        $evento->estado_dinamico = $evento->estado_dinamico;

        if ($user->hasRole('Estudiante')) {
            $participacion = $evento->participantes()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            $estaInscritoActivamente = $participacion
                && in_array($participacion->estado, ['pendiente', 'aprobado', 'asistio'], true);

            $evento->esta_inscrito = $estaInscritoActivamente;
            $evento->estado_inscripcion = $participacion?->estado;
            $evento->fue_rechazado = $participacion?->estado === 'rechazado';
            $evento->cupos_disponibles = $evento->cupos_disponibles;
            $evento->propuesta_rechazada = $evento->fue_rechazado
                ? $this->propuestaRechazadaPayload($participacion)
                : null;
        }

        return Inertia::render('Eventos/Show', [
            'evento' => $evento,
            'modalidades' => CatalogosEvento::MODALIDADES,
            'areasParticipacion' => CatalogosEvento::AREAS_PARTICIPACION,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function edit(Evento $evento): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para editar eventos.');
        }

        return Inertia::render('Eventos/Edit', [
            'evento' => $evento,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function update(UpdateEventoRequest $request, Evento $evento): RedirectResponse
    {
        $validated = $this->normalizeEventoData($request->validated());

        if ($request->hasFile('imagen')) {
            if ($evento->imagen) {
                Storage::disk('public')->delete($evento->imagen);
            }

            $validated['imagen'] = $request->file('imagen')->store('eventos', 'public');
        } else {
            // El formulario envía el campo vacío cuando no se elige una imagen
            // nueva; sin descartarlo aquí, la actualización borraría la que ya
            // tenía el evento y habría que volver a subirla en cada edición.
            unset($validated['imagen']);
        }

        $evento->update($validated);

        return redirect()->route('eventos.index')
            ->with('success', 'Evento actualizado exitosamente.');
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            return redirect()->back()
                ->with('error', 'No tienes permisos para eliminar eventos.');
        }

        if ($evento->imagen) {
            Storage::disk('public')->delete($evento->imagen);
        }

        $evento->delete();

        return redirect()->route('eventos.index')
            ->with('success', 'Evento eliminado exitosamente.');
    }

    public function inscribir(Request $request, Evento $evento): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole('Estudiante')) {
            return redirect()->back()
                ->with('error', 'Solo los estudiantes pueden inscribirse a eventos.');
        }

        $estadoDinamico = $evento->estado_dinamico;
        if (!in_array($estadoDinamico, ['activo'], true)) {
            $mensaje = match ($estadoDinamico) {
                'en_curso' => 'Este evento ya está en curso. No se permiten más inscripciones.',
                'finalizado' => 'Este evento ya ha finalizado.',
                'inactivo' => 'Este evento no está disponible para inscripciones.',
                default => 'Este evento no está disponible para inscripciones.',
            };

            return redirect()->back()->with('error', $mensaje);
        }

        $participacionAnterior = EventoParticipante::where('evento_id', $evento->id)
            ->where('user_id', $user->id)
            ->first();

        // Una propuesta rechazada NO bloquea al estudiante: puede corregirla y
        // reenviarla para que vuelva a pasar por revisión. Sólo se actualiza el
        // mismo registro (más abajo) en lugar de borrarlo, porque
        // evento_participantes tiene una clave única (evento_id, user_id) y no
        // admitiría una segunda fila para el mismo estudiante en el mismo evento.
        if ($participacionAnterior) {
            if (in_array($participacionAnterior->estado, ['pendiente', 'aprobado', 'asistio'], true)) {
                return redirect()->back()
                    ->with('error', 'Ya estás inscrito en este evento.');
            }

            if ($participacionAnterior->estado === 'cancelado') {
                $participacionAnterior->delete();
                $participacionAnterior = null;
            }
        }

        if (!$evento->tieneCuposDisponibles()) {
            return redirect()->back()
                ->with('error', 'Este evento ya no tiene cupos disponibles.');
        }

        $validated = $request->validate([
            'titulo_proyecto' => 'required|string|max:255',
            'descripcion_proyecto' => 'required|string|max:2000',
            'archivo_proyecto' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'modalidad' => 'required|string|in:' . implode(',', CatalogosEvento::MODALIDADES),
            'area_participacion' => 'required|string|in:' . implode(',', CatalogosEvento::AREAS_PARTICIPACION),
            'participantes' => 'required|array|min:1',
            'participantes.*.nombre' => 'required|string|max:255',
            'participantes.*.email' => 'required|email|max:255',
        ], [
            'titulo_proyecto.required' => 'El título del proyecto es obligatorio.',
            'descripcion_proyecto.required' => 'La descripción del proyecto es obligatoria.',
            'archivo_proyecto.required' => 'Debes subir el archivo del proyecto.',
            'archivo_proyecto.mimes' => 'El archivo debe ser PDF, DOC o DOCX.',
            'archivo_proyecto.max' => 'El archivo no puede superar los 10MB.',
            'modalidad.required' => 'Debes seleccionar la modalidad de participación.',
            'modalidad.in' => 'La modalidad seleccionada no es válida.',
            'area_participacion.required' => 'Debes seleccionar el área de participación.',
            'area_participacion.in' => 'El área de participación seleccionada no es válida.',
            'participantes.required' => 'Debes agregar al menos un participante.',
            'participantes.*.nombre.required' => 'El nombre del participante es obligatorio.',
            'participantes.*.email.required' => 'El email del participante es obligatorio.',
            'participantes.*.email.email' => 'El email del participante debe ser valido.',
        ]);

        try {
            // Si se corrige una propuesta rechazada, el archivo anterior se
            // reemplaza: se borra del disco para no acumular versiones viejas
            // que ya nadie va a abrir.
            if ($participacionAnterior?->archivo_proyecto) {
                Storage::disk('public')->delete($participacionAnterior->archivo_proyecto);
            }

            $archivoPath = null;
            if ($request->hasFile('archivo_proyecto')) {
                $file = $request->file('archivo_proyecto');
                $fileName = time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                $archivoPath = $file->storeAs('proyectos_eventos', $fileName, 'public');
            }

            $datos = [
                'evento_id' => $evento->id,
                'user_id' => $user->id,
                'modalidad' => $validated['modalidad'],
                'area_participacion' => $validated['area_participacion'],
                'estado' => 'pendiente',
                'titulo_proyecto' => $validated['titulo_proyecto'],
                'descripcion_proyecto' => $validated['descripcion_proyecto'],
                'archivo_proyecto' => $archivoPath,
                'participantes' => $validated['participantes'],
            ];

            if ($participacionAnterior) {
                // Corrección de una propuesta rechazada: se actualiza el mismo
                // registro y sube el contador de envíos, para que en la
                // bandeja del coordinador se note que es un reenvío.
                $datos['intentos_envio'] = $participacionAnterior->intentos_envio + 1;
                $participacionAnterior->update($datos);
            } else {
                EventoParticipante::create($datos);
            }

            return redirect()->back()
                ->with('success', 'Tu propuesta ha sido enviada y está pendiente de revisión.');
        } catch (\Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? null) == 1062) {
                return redirect()->back()
                    ->with('error', 'Ya estás inscrito en este evento.');
            }

            throw $e;
        }
    }

    public function cancelarInscripcion(Evento $evento): RedirectResponse
    {
        $user = auth()->user();

        $participacion = EventoParticipante::where('evento_id', $evento->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participacion) {
            return redirect()->back()
                ->with('error', 'No estás inscrito en este evento.');
        }

        $participacion->update(['estado' => 'cancelado']);

        return redirect()->back()
            ->with('success', 'Tu inscripcion ha sido cancelada.');
    }

    public function participantes(Evento $evento): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para ver esta informacion.');
        }

        $participantes = EventoParticipante::where('evento_id', $evento->id)
            ->with(['usuario', 'revisor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Eventos/Participantes', [
            'evento' => $evento,
            'participantes' => $participantes,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function propuestasPendientes(): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para ver esta informacion.');
        }

        $propuestas = EventoParticipante::pendientes()
            ->with(['evento', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Eventos/PropuestasPendientes', [
            'propuestas' => $propuestas,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function verPropuesta(EventoParticipante $participante): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para ver esta informacion.');
        }

        $participante->load(['evento', 'usuario', 'revisor']);

        return Inertia::render('Eventos/VerPropuesta', [
            'propuesta' => $participante,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function aprobarPropuesta(Request $request, EventoParticipante $participante): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            return redirect()->back()
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $validated = $request->validate([
            'programacion' => 'required|string|max:1000',
            'aula' => 'required|string|max:100',
            'horario' => 'required|string|max:255',
            'evaluador_id' => 'required|exists:users,id',
        ], [
            'programacion.required' => 'La programación es obligatoria.',
            'aula.required' => 'El aula es obligatoria.',
            'horario.required' => 'El horario es obligatorio.',
            'evaluador_id.required' => 'Debes seleccionar un evaluador.',
            'evaluador_id.exists' => 'El evaluador seleccionado no existe.',
        ]);

        $participante->update([
            'estado' => 'aprobado',
            'programacion' => $validated['programacion'],
            'aula' => $validated['aula'],
            'horario' => $validated['horario'],
            'fecha_revision' => now(),
            'revisado_por' => $user->id,
            'evaluador_id' => $validated['evaluador_id'],
            'estado_evaluacion' => 'sin_evaluar',
        ]);

        $this->avisarAlEvaluador($participante, $user);

        return redirect()->back()->with(
            'success',
            'Propuesta aprobada y evaluador asignado exitosamente. El estudiante puede ver la informacion en "Mis Inscripciones".'
        );
    }

    public function rechazarPropuesta(Request $request, EventoParticipante $participante): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            return redirect()->back()
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $validated = $request->validate([
            'motivo_rechazo' => 'required|string|max:1000',
        ], [
            'motivo_rechazo.required' => 'Debes proporcionar un motivo de rechazo.',
        ]);

        $participante->update([
            'estado' => 'rechazado',
            'motivo_rechazo' => $validated['motivo_rechazo'],
            'fecha_revision' => now(),
            'revisado_por' => $user->id,
        ]);

        return redirect()->back()
            ->with('success', 'Propuesta rechazada. El estudiante puede ver el feedback en "Mis Inscripciones".');
    }

    public function getEvaluadoresDisponibles()
    {
        $evaluadores = User::role(['Jurado', 'Docente/Tutor'])
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($evaluadores);
    }

    public function proyectosAsignados(): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Jurado', 'Docente/Tutor'])) {
            abort(403, 'No tienes permisos para acceder a esta seccion.');
        }

        $asignados = EventoParticipante::where('evaluador_id', $user->id)
            ->where('estado', 'aprobado');

        // Esta pantalla es la bandeja de trabajo: sólo lo que falta por
        // calificar. Las notas ya puestas se consultan en "Mis evaluaciones".
        $proyectos = (clone $asignados)
            ->where('estado_evaluacion', '!=', 'evaluado')
            ->with(['evento', 'usuario'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Eventos/ProyectosAsignados', [
            'proyectos' => $proyectos,
            'totalEvaluados' => (clone $asignados)->where('estado_evaluacion', 'evaluado')->count(),
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function mostrarFormularioEvaluacion(EventoParticipante $participante): Response
    {
        $user = auth()->user();

        if ($participante->evaluador_id !== $user->id) {
            abort(403, 'No estás asignado para evaluar este proyecto.');
        }

        $participante->load(['evento', 'usuario']);

        // Se usan las rúbricas de ese evento+modalidad; si no hay ninguna
        // específica, las genéricas sirven de respaldo. orderByRaw deja la
        // coincidencia exacta primero, para preseleccionarla en pantalla.
        $rubricas = Rubrica::with('criterios')
            ->paraEventoYModalidad($participante->evento_id, $participante->modalidad)
            ->orderByRaw('(evento_id = ? AND modalidad = ?) DESC', [$participante->evento_id, $participante->modalidad])
            ->get();

        $evaluacionExistente = Evaluacion::where('evento_participante_id', $participante->id)
            ->where('jurado_id', $user->id)
            ->with('detalleEvaluaciones.criterio')
            ->first();

        return Inertia::render('Eventos/EvaluarProyecto', [
            'participante' => $participante,
            'rubricas' => $rubricas,
            'evaluacionExistente' => $evaluacionExistente,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function evaluarProyecto(Request $request, EventoParticipante $participante): RedirectResponse
    {
        $user = auth()->user();

        if ($participante->evaluador_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'No estás asignado para evaluar este proyecto.');
        }

        $validated = $request->validate([
            'rubrica_id' => 'required|exists:rubricas,id',
            'puntajes' => 'required|array',
            'puntajes.*' => 'required|integer|min:1',
            'analisis_proyecto' => 'required|string|max:2000',
            'fortalezas' => 'required|string|max:1000',
            'debilidades' => 'required|string|max:1000',
            'recomendaciones' => 'required|string|max:1000',
        ], [
            'rubrica_id.required' => 'Debes seleccionar una rúbrica.',
            'puntajes.required' => 'Debes calificar cada criterio de la rúbrica.',
            'analisis_proyecto.required' => 'El análisis del proyecto es obligatorio.',
            'fortalezas.required' => 'Debes indicar las fortalezas del proyecto.',
            'debilidades.required' => 'Debes indicar las debilidades del proyecto.',
            'recomendaciones.required' => 'Las recomendaciones son obligatorias.',
        ]);

        DB::transaction(function () use ($validated, $user, $participante) {
            $evaluacion = Evaluacion::updateOrCreate(
                [
                    'evento_participante_id' => $participante->id,
                    'jurado_id' => $user->id,
                ],
                [
                    'rubrica_id' => $validated['rubrica_id'],
                    'observaciones' => $validated['analisis_proyecto'],
                    'estado' => 'Pendiente',
                ]
            );

            $evaluacion->detalleEvaluaciones()->delete();

            foreach ($validated['puntajes'] as $criterioId => $puntaje) {
                $criterio = CriterioRubrica::findOrFail($criterioId);

                // El criterio debe pertenecer a la rúbrica elegida. Sin esta
                // comprobación, un puntaje que quedó del formulario de otra
                // rúbrica (por ejemplo si el jurado cambió de rúbrica sin que
                // la pantalla limpiara los campos) se guardaría igual, aunque
                // ese criterio no tenga nada que ver con esta evaluación.
                if ($criterio->rubrica_id !== $validated['rubrica_id']) {
                    throw new \Exception("El criterio '{$criterio->criterio}' no pertenece a la rúbrica seleccionada.");
                }

                if ($puntaje < 1 || $puntaje > $criterio->puntaje_maximo) {
                    throw new \Exception("El puntaje para '{$criterio->criterio}' debe estar entre 1 y {$criterio->puntaje_maximo}.");
                }

                DetalleEvaluacion::create([
                    'evaluacion_id' => $evaluacion->id,
                    'criterio_id' => $criterioId,
                    'puntaje' => $puntaje,
                ]);
            }

            $evaluacion->completarEvaluacion();

            // Se mantienen en sincronía las columnas de evento_participantes:
            // el resto del sistema (reportes, "Mis inscripciones", scopes de
            // evaluación) ya lee la nota y el estado desde aquí.
            $participante->update([
                'nota_final' => $evaluacion->nota_final,
                'analisis_proyecto' => $validated['analisis_proyecto'],
                'fortalezas' => $validated['fortalezas'],
                'debilidades' => $validated['debilidades'],
                'recomendaciones' => $validated['recomendaciones'],
                'estado_evaluacion' => 'evaluado',
                'fecha_evaluacion' => now(),
            ]);
        });

        return redirect()->route('proyectos.asignados')
            ->with('success', 'Evaluacion guardada exitosamente. El estudiante podra ver su calificacion.');
    }

    /**
     * Avisa al jurado de que tiene un proyecto nuevo por evaluar.
     *
     * Antes la asignación era silenciosa: el jurado sólo se enteraba si entraba
     * por su cuenta a "Evaluar proyectos".
     */
    private function avisarAlEvaluador(EventoParticipante $participante, User $asignadoPor): void
    {
        $participante->loadMissing('evento');

        $titulo = $participante->titulo_proyecto ?: 'un proyecto';
        $evento = $participante->evento?->titulo;

        $detalles = array_filter([
            $evento ? "Evento: {$evento}" : null,
            $participante->aula ? "Aula: {$participante->aula}" : null,
            $participante->horario ? "Horario: {$participante->horario}" : null,
        ]);

        \App\Models\Notificacion::crear(
            $participante->evaluador_id,
            'Tienes un proyecto nuevo por evaluar',
            "Se te asignó «{$titulo}» para evaluar."
                . ($detalles ? ' ' . implode(' · ', $detalles) . '.' : ''),
            'info',
            [
                'evento_participante_id' => $participante->id,
                'evento_id' => $participante->evento_id,
            ],
            $asignadoPor->id
        );
    }

    /**
     * Datos de la propuesta rechazada que el estudiante puede corregir.
     *
     * Se envían a la pantalla para dos cosas: mostrar el motivo del rechazo
     * sin que el estudiante tenga que ir a otra pantalla a buscarlo, y
     * prellenar el formulario de reenvío con lo que ya había escrito, para
     * que sólo tenga que corregir lo que el revisor señaló.
     */
    private function propuestaRechazadaPayload(EventoParticipante $participacion): array
    {
        return [
            'motivo_rechazo' => $participacion->motivo_rechazo,
            'fecha_revision' => $participacion->fecha_revision,
            'titulo_proyecto' => $participacion->titulo_proyecto,
            'descripcion_proyecto' => $participacion->descripcion_proyecto,
            'modalidad' => $participacion->modalidad,
            'area_participacion' => $participacion->area_participacion,
            'participantes' => $participacion->participantes,
            'intentos_envio' => $participacion->intentos_envio,
        ];
    }

    private function normalizeEventoData(array $validated): array
    {
        // Estos campos son opcionales: si el formulario no los envía, la clave
        // no existe en los datos validados y acceder a ella provocaba un error.
        foreach (['cupo_maximo', 'ubicacion', 'fecha_fin'] as $campo) {
            $validated[$campo] = ($validated[$campo] ?? null) ?: null;
        }

        return $validated;
    }
}
