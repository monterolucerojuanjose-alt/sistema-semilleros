<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\EventoParticipante;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DEL HISTORIAL DE INSCRIPCIONES
 * ============================================================================
 *
 * Pantalla de consulta para la coordinación: reúne todas las inscripciones a
 * eventos del sistema, con buscador, filtros y un resumen de cifras.
 *
 * Es un módulo de solo lectura. No crea, modifica ni elimina nada: sirve para
 * responder preguntas como «¿cuántos se inscribieron al congreso?» o «¿en qué
 * quedó la solicitud de tal estudiante?».
 *
 * Acceso restringido a Administrador y Coordinador.
 */
class HistorialInscripcionesController extends Controller
{
    /**
     * Restricciones que se aplican antes de ejecutar cualquier método.
     *
     * El control está en el constructor y no dentro de cada método para que
     * ninguna acción futura quede desprotegida por descuido.
     */
    public function __construct()
    {
        // Exige tener la sesión iniciada.
        $this->middleware('auth');

        // Exige además uno de los dos roles autorizados.
        $this->middleware('role:Administrador,Coordinador');
    }

    /**
     * Muestra el historial completo de inscripciones.
     *
     * @param  Request  $request  Petición con los filtros de búsqueda.
     * @return Response La pantalla HistorialInscripciones/Index.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Segunda comprobación de permisos, además de la del constructor.
        // Es deliberadamente redundante: si alguien registrara una ruta nueva
        // hacia este método sin el middleware, este control seguiría negando
        // el paso.
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a esta información.');
        }

        // Filtros que llegan de la barra de búsqueda. El segundo argumento de
        // «get» es el valor por defecto, para que la primera visita a la
        // pantalla, sin ningún filtro, no produzca valores nulos.
        $search = $request->get('search', '');
        $estado = $request->get('estado', '');
        $evento = $request->get('evento', '');

        // «with» carga de antemano usuario, evento y evaluador. Sin esta
        // línea, pintar quince filas lanzaría cuarenta y cinco consultas
        // adicionales, una por cada dato relacionado de cada fila.
        $query = EventoParticipante::with(['usuario', 'evento', 'evaluador'])
            ->orderBy('created_at', 'desc');

        // El buscador mira en el nombre y el correo del estudiante, que es
        // por donde se busca a una persona en la práctica.
        if ($search) {
            $query->whereHas('usuario', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        if ($evento) {
            $query->where('evento_id', $evento);
        }

        // Se pagina de quince en quince en lugar de traerlo todo: el historial
        // crece sin límite y cargarlo entero acabaría agotando la memoria.
        $inscripciones = $query->paginate(15);

        // Lista de eventos para el desplegable del filtro. Se piden solo tres
        // columnas porque es lo único que necesita el desplegable.
        $eventos = Evento::select('id', 'titulo', 'fecha_inicio')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Cifras de la cabecera. Se calculan sobre el total del sistema y no
        // sobre los resultados filtrados: son un panorama general, no un
        // resumen de la búsqueda en curso.
        $estadisticas = [
            'total' => EventoParticipante::count(),
            'pendientes' => EventoParticipante::where('estado', 'pendiente')->count(),
            'aprobados' => EventoParticipante::where('estado', 'aprobado')->count(),
            'rechazados' => EventoParticipante::where('estado', 'rechazado')->count(),
            'cancelados' => EventoParticipante::where('estado', 'cancelado')->count(),
        ];

        return Inertia::render('HistorialInscripciones/Index', [
            'inscripciones' => $inscripciones,
            'eventos' => $eventos,
            'estadisticas' => $estadisticas,

            // Los filtros se devuelven a la pantalla para que los campos
            // conserven lo escrito después de recargar los resultados.
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'evento' => $evento,
            ]
        ]);
    }

    /**
     * Muestra el detalle de una inscripción concreta.
     *
     * @param  int  $id  Identificador de la inscripción.
     * @return Response La pantalla HistorialInscripciones/Show.
     */
    public function show($id): Response
    {
        // «findOrFail» devuelve un error 404 si el registro no existe, en
        // lugar de seguir con un valor vacío y reventar más adelante al
        // intentar leer una propiedad de la nada.
        $inscripcion = EventoParticipante::with(['usuario', 'evento', 'evaluador'])
            ->findOrFail($id);

        return Inertia::render('HistorialInscripciones/Show', [
            'inscripcion' => $inscripcion
        ]);
    }
}
