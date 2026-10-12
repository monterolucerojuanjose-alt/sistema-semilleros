<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\EventoParticipante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR «MIS NOTAS» (VISTA DEL ESTUDIANTE)
 * ============================================================================
 *
 * Reúne en una sola pantalla todas las calificaciones de un estudiante.
 *
 * El problema que resuelve: un estudiante puede recibir notas por dos vías
 * distintas, guardadas en tablas distintas y con nombres de campo distintos.
 *
 *   1. Proyectos del semillero, calificados por un jurado con rúbrica.
 *      → tabla «evaluaciones»
 *   2. Propuestas presentadas a eventos académicos.
 *      → tabla «evento_participantes»
 *
 * Para el estudiante esa distinción es irrelevante: él quiere ver sus notas.
 * Este controlador consulta ambos orígenes y los convierte a una estructura
 * común, de modo que la pantalla trate todas las notas por igual sin saber
 * de dónde vienen.
 *
 * Acceso restringido al rol Estudiante.
 */
class MisNotasController extends Controller
{
    /**
     * Nota mínima para dar por aprobada una evaluación.
     *
     * Corresponde a la escala institucional de 0.0 a 100.0. Se declara como
     * constante para que el criterio de aprobación esté en un único sitio,
     * y además se envía a la pantalla para que allí no se repita el número.
     */
    private const NOTA_APROBATORIA = 60.0;

    /**
     * Muestra todas las notas del estudiante que tiene la sesión abierta.
     *
     * @return Response La pantalla Estudiante/MisNotas.
     */
    public function index(): Response
    {
        $user = auth()->user();

        if (!$user->hasRole('Estudiante')) {
            abort(403, 'No tienes permisos para ver esta sección.');
        }

        // Las notas ya publicadas. Se piden a los dos orígenes, se unen con
        // «concat» y se ordenan de la más reciente a la más antigua. El
        // ordenamiento se hace después de unir, no antes: ordenar cada lista
        // por separado dejaría las dos intercaladas de forma incorrecta.
        $evaluadas = $this->notasDeEventos($user->id, true)
            ->concat($this->notasDeProyectos($user->id, true))
            ->sortByDesc('fecha')
            ->values();

        // Lo que aún está sin calificar, por el mismo procedimiento.
        $pendientes = $this->notasDeEventos($user->id, false)
            ->concat($this->notasDeProyectos($user->id, false))
            ->sortByDesc('fecha')
            ->values();

        return Inertia::render('Estudiante/MisNotas', [
            'evaluadas' => $evaluadas,
            'pendientes' => $pendientes,
            'resumen' => $this->construirResumen($evaluadas, $pendientes),
            'notaAprobatoria' => self::NOTA_APROBATORIA,
            'auth' => [
                // Se cargan los roles porque el menú lateral los necesita
                // para decidir qué opciones mostrar.
                'user' => $user->load('roles'),
            ],
        ]);
    }

    /**
     * Obtiene las notas que el estudiante recibió en eventos académicos.
     *
     * @param  int   $userId     Identificador del estudiante.
     * @param  bool  $evaluadas  true para las ya calificadas, false para las pendientes.
     * @return Collection Notas con la estructura común de la pantalla.
     */
    private function notasDeEventos(int $userId, bool $evaluadas): Collection
    {
        return EventoParticipante::where('user_id', $userId)

            // Solo cuentan las propuestas admitidas: una rechazada nunca
            // llegó a calificarse y no debe figurar como nota pendiente.
            ->where('estado', 'aprobado')

            // Y solo aquellas con jurado asignado. Sin evaluador designado
            // todavía no hay nada que esperar, así que no se muestra al
            // estudiante como algo en curso.
            ->whereNotNull('evaluador_id')

            // «when» elige la condición según lo que se haya pedido: las
            // terminadas, o las que siguen en proceso. Evita duplicar el
            // método entero para cambiar una sola línea.
            ->when(
                $evaluadas,
                fn ($q) => $q->where('estado_evaluacion', 'evaluado'),
                fn ($q) => $q->whereIn('estado_evaluacion', ['sin_evaluar', 'en_revision'])
            )

            // Se piden solo las columnas necesarias de cada relación.
            ->with(['evento:id,titulo', 'evaluador:id,name'])
            ->get()

            // Conversión a la estructura común que entiende la pantalla.
            ->map(fn (EventoParticipante $p) => [
                // El prefijo «evento-» evita que un registro de esta tabla
                // choque con uno de «evaluaciones» que tenga el mismo número.
                // Sin él, Vue reutilizaría filas al dibujar la lista y
                // mostraría datos mezclados.
                'id' => 'evento-'.$p->id,

                'tipo' => 'evento',
                'tipo_texto' => 'Evento académico',
                'titulo' => $p->titulo_proyecto,

                // El operador «?->» consulta la relación solo si existe. Si
                // el evento fue eliminado, se muestra un texto en lugar de
                // dejar la pantalla en blanco por leer una propiedad de null.
                'contexto' => $p->evento?->titulo ?? 'Evento eliminado',
                'evaluador' => $p->evaluador?->name,

                // Mientras no esté evaluada no se envía nota: un cero haría
                // creer al estudiante que ya lo calificaron y reprobó.
                'nota' => $evaluadas ? (float) $p->nota_final : null,

                // Para las evaluadas interesa cuándo se calificó; para las
                // pendientes, desde cuándo esperan.
                'fecha' => $evaluadas ? $p->fecha_evaluacion : $p->created_at,

                'estado_texto' => $evaluadas
                    ? 'Evaluado'
                    : ($p->estado_evaluacion === 'en_revision' ? 'En revisión' : 'Sin evaluar'),

                'observaciones' => $p->analisis_proyecto,

                // Solo las propuestas de evento tienen ficha de detalle con
                // fortalezas y recomendaciones. En las demás va null, y la
                // pantalla oculta el botón «Ver detalle».
                'detalle_id' => $evaluadas ? $p->id : null,
            ]);
    }

    /**
     * Obtiene las notas de los proyectos del semillero, calificados con rúbrica.
     *
     * @param  int   $userId     Identificador del estudiante.
     * @param  bool  $evaluadas  true para las ya calificadas, false para las pendientes.
     * @return Collection Notas con la estructura común de la pantalla.
     */
    private function notasDeProyectos(int $userId, bool $evaluadas): Collection
    {
        // El autor no es un campo de «evaluaciones», sino del proyecto: de ahí
        // que se filtre con «whereHas» sobre la relación.
        return Evaluacion::whereHas('proyecto', fn ($q) => $q->where('autor_id', $userId))
            ->where('estado', $evaluadas ? 'Evaluado' : 'Pendiente')
            ->with(['proyecto:id,titulo,semillero_id', 'proyecto.semillero:id,nombre', 'jurado:id,name'])
            ->get()
            ->map(fn (Evaluacion $e) => [
                'id' => 'proyecto-'.$e->id,
                'tipo' => 'proyecto',
                'tipo_texto' => 'Proyecto de semillero',
                'titulo' => $e->proyecto?->titulo ?? 'Proyecto eliminado',
                'contexto' => $e->proyecto?->semillero?->nombre ?? 'Sin semillero',
                'evaluador' => $e->jurado?->name,
                'nota' => $evaluadas ? (float) $e->nota_final : null,

                // Aquí se usa «updated_at» como fecha de calificación: es el
                // momento en que el jurado cerró la evaluación, porque esta
                // tabla no guarda una fecha propia de evaluación.
                'fecha' => $evaluadas ? $e->updated_at : $e->created_at,

                'estado_texto' => $evaluadas ? 'Evaluado' : 'Pendiente',
                'observaciones' => $e->observaciones,

                // Los proyectos de semillero no tienen ficha de detalle.
                'detalle_id' => null,
            ]);
    }

    /**
     * Calcula los indicadores de la cabecera de la pantalla.
     *
     * @param  Collection  $evaluadas   Notas ya publicadas.
     * @param  Collection  $pendientes  Evaluaciones aún sin cerrar.
     * @return array<string, mixed> Total, pendientes, promedio, mejor nota y aprobadas.
     */
    private function construirResumen(Collection $evaluadas, Collection $pendientes): array
    {
        // «filter» descarta los valores nulos antes de calcular. Sin este
        // paso, una nota ausente contaría como cero y hundiría el promedio.
        $notas = $evaluadas->pluck('nota')->filter(fn ($n) => $n !== null);

        return [
            'total' => $evaluadas->count(),
            'pendientes' => $pendientes->count(),

            // Se devuelve null y no cero cuando no hay notas: la pantalla
            // muestra un guion, que es más honesto que un «0.00» que parece
            // una calificación real.
            'promedio' => $notas->isEmpty() ? null : round($notas->avg(), 2),
            'mejor_nota' => $notas->isEmpty() ? null : round($notas->max(), 2),

            'aprobadas' => $notas->filter(fn ($n) => $n >= self::NOTA_APROBATORIA)->count(),
        ];
    }

    /**
     * Muestra el detalle de la evaluación de una propuesta de evento.
     *
     * Incluye el análisis del jurado: fortalezas, debilidades y recomendaciones.
     *
     * @param  EventoParticipante  $propuesta  Propuesta a consultar, inyectada por la ruta.
     * @return Response|RedirectResponse El detalle, o una redirección si aún no hay nota.
     */
    public function show(EventoParticipante $propuesta): Response|RedirectResponse
    {
        $user = auth()->user();

        // Comprobación imprescindible: sin ella, cambiar el número de la
        // dirección permitiría leer la evaluación de cualquier compañero.
        if ($propuesta->user_id !== $user->id) {
            abort(403, 'No tienes permisos para ver esta evaluación.');
        }

        // Si todavía no hay nota se vuelve al listado con un aviso, en vez de
        // mostrar una ficha vacía sin explicación.
        if ($propuesta->estado_evaluacion !== 'evaluado') {
            return redirect()->route('mis-notas')
                ->with('error', 'Esta propuesta aún no ha sido evaluada.');
        }

        $propuesta->load(['evento', 'evaluador', 'usuario']);

        return Inertia::render('Estudiante/DetalleNota', [
            'propuesta' => $propuesta,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }
}
