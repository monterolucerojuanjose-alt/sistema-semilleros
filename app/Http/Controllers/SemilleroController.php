<?php

namespace App\Http\Controllers;

use App\Models\Semillero;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SemilleroController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra el listado de registros.
     */
    public function index(): Response|RedirectResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->route('login');
            }

            // Todos los roles pueden ver todos los semilleros, pero con diferentes niveles de información
            $query = Semillero::with(['tutor', 'miembrosAprobados']);

            // Cargar solicitudes pendientes para todos los usuarios
            $query->with('solicitudesPendientes');

            $semilleros = $query->orderBy('created_at', 'desc')->paginate(10);

            return Inertia::render('Semilleros/Index', [
                'semilleros' => $semilleros,
                'auth' => [
                    'user' => $user->load('roles')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en SemilleroController@index: ' . $e->getMessage());
            return Inertia::render('Semilleros/Index', [
                'semilleros' => collect([]),
                'auth' => [
                    'user' => auth()->user()
                ],
                'error' => 'Error al cargar los semilleros'
            ]);
        }
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        // Solo administradores y coordinadores pueden crear semilleros
        $this->authorize('create', Semillero::class);

        $tutores = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Docente/Tutor', 'Coordinador']);
        })->get();

        return Inertia::render('Semilleros/Create', [
            'tutores' => $tutores,
            'auth' => [
                'user' => auth()->user()->load('roles')
            ]
        ]);
    }

    /**
     * Guarda un registro nuevo en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Semillero::class);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'linea_tematica' => 'required|string|max:255',
            'tutor_id' => 'required|exists:users,id',
            'cupo_maximo' => 'nullable|integer|min:1|max:500',
            'clave_acceso' => 'nullable|string|min:4|max:50',
        ], [
            'cupo_maximo.min' => 'El cupo debe ser al menos de 1 integrante.',
            'cupo_maximo.max' => 'El cupo no puede superar los 500 integrantes.',
            'clave_acceso.min' => 'La clave debe tener al menos 4 caracteres.',
        ]);

        Semillero::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'linea_tematica' => $datos['linea_tematica'],
            'tutor_id' => $datos['tutor_id'],
            'cupo_maximo' => $datos['cupo_maximo'] ?? null,
            // La clave se guarda cifrada; nunca en texto plano.
            'clave_acceso' => filled($datos['clave_acceso'] ?? null)
                ? Hash::make($datos['clave_acceso'])
                : null,
            'estado' => 'activo',
        ]);

        return redirect()->route('semilleros.index')
            ->with('success', 'Semillero creado exitosamente.');
    }

    /**
     * Muestra el detalle de un registro concreto.
     */
    public function show(Semillero $semillero): Response
    {
        $semillero->load(['tutor', 'miembrosAprobados', 'solicitudesPendientes']);

        return Inertia::render('Semilleros/Show', [
            'semillero' => $semillero,
            'auth' => [
                'user' => auth()->user()->load('roles')
            ]
        ]);
    }

    /**
     * Muestra el formulario de edicion.
     */
    public function edit(Semillero $semillero): Response
    {
        $this->authorize('update', $semillero);

        $tutores = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Docente/Tutor', 'Coordinador']);
        })->get();

        return Inertia::render('Semilleros/Edit', [
            'semillero' => $semillero,
            'tutores' => $tutores,
            'auth' => [
                'user' => auth()->user()->load('roles')
            ]
        ]);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Request $request, Semillero $semillero): RedirectResponse
    {
        $this->authorize('update', $semillero);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'linea_tematica' => 'required|string|max:255',
            'tutor_id' => 'required|exists:users,id',
            'estado' => 'required|in:activo,inactivo',
            'cupo_maximo' => 'nullable|integer|min:1|max:500',
            'clave_acceso' => 'nullable|string|min:4|max:50',
            'quitar_clave' => 'nullable|boolean',
        ], [
            'clave_acceso.min' => 'La clave debe tener al menos 4 caracteres.',
        ]);

        // Se asignan campo por campo: con $request->all() se podría guardar la
        // clave sin cifrar, ya que ahora es un atributo asignable del modelo.
        $semillero->fill([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'linea_tematica' => $datos['linea_tematica'],
            'tutor_id' => $datos['tutor_id'],
            'estado' => $datos['estado'],
            'cupo_maximo' => $datos['cupo_maximo'] ?? null,
        ]);

        // La clave sólo cambia si escriben una nueva o piden quitarla; de lo
        // contrario se conserva la que ya tenía.
        if (! empty($datos['quitar_clave'])) {
            $semillero->clave_acceso = null;
        } elseif (filled($datos['clave_acceso'] ?? null)) {
            $semillero->clave_acceso = Hash::make($datos['clave_acceso']);
        }

        $semillero->save();

        return redirect()->route('semilleros.index')
            ->with('success', 'Semillero actualizado exitosamente.');
    }

    /**
     * Elimina un registro de la base de datos.
     */
    public function destroy(Semillero $semillero): RedirectResponse
    {
        $user = auth()->user();

        // Solo Administradores y Coordinadores pueden eliminar semilleros
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para eliminar semilleros.');
        }

        // Verificar que el semillero no tenga proyectos asociados
        if ($semillero->proyectos()->count() > 0) {
            return redirect()->route('semilleros.index')
                ->with('error', 'No se puede eliminar el semillero porque tiene proyectos asociados.');
        }

        // Verificar que el semillero no tenga miembros
        if ($semillero->miembrosAprobados()->count() > 0) {
            return redirect()->route('semilleros.index')
                ->with('error', 'No se puede eliminar el semillero porque tiene miembros activos.');
        }

        $semillero->delete();

        return redirect()->route('semilleros.index')
            ->with('success', 'Semillero eliminado exitosamente.');
    }

    /**
     * Estudiante solicita unirse a un semillero
     */
    public function requestJoin(Request $request, Semillero $semillero): RedirectResponse
    {
        $user = auth()->user();

        // Verificar que el usuario sea estudiante
        if (!$user->hasRole('Estudiante')) {
            return redirect()->back()
                ->with('error', 'Solo los estudiantes pueden solicitar unirse a semilleros.');
        }

        // Verificar que el semillero esté activo
        if ($semillero->estado !== 'activo') {
            return redirect()->back()
                ->with('error', 'Este semillero está inactivo y no admite nuevos integrantes.');
        }

        // Verificar que quede cupo
        if ($semillero->estaLleno()) {
            return redirect()->back()
                ->with('error', 'Este semillero ya alcanzó su cupo máximo de integrantes.');
        }

        // Semilleros con clave: sólo entra quien la conoce
        if ($semillero->requiere_clave && ! $semillero->claveEsCorrecta($request->input('clave_acceso'))) {
            return back()->withErrors([
                'clave_acceso' => 'La clave del semillero no es correcta.',
            ]);
        }

        // Verificar que no tenga una solicitud pendiente
        $existingRequest = $semillero->usuarios()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('estado_solicitud', 'pendiente')
            ->exists();

        if ($existingRequest) {
            return redirect()->back()
                ->with('error', 'Ya tienes una solicitud pendiente para este semillero.');
        }

        // Crear la solicitud
        $semillero->usuarios()->attach($user->id, [
            'rol_en_semillero' => 'estudiante',
            'estado_solicitud' => 'pendiente',
            'fecha' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Solicitud enviada exitosamente. Espera la aprobación del coordinador.');
    }

    /**
     * Coordinador aprueba o rechaza una solicitud
     */
    public function approveJoin(Request $request, Semillero $semillero, User $user): RedirectResponse
    {
        $this->authorize('approveJoin', $semillero);

        $request->validate([
            'estado' => 'required|in:aprobado,rechazado',
        ]);

        // Aprobar no debe poder exceder el cupo definido para el semillero.
        if ($request->estado === 'aprobado' && $semillero->estaLleno()) {
            return redirect()->back()
                ->with('error', 'El semillero ya alcanzó su cupo máximo de integrantes.');
        }

        $semillero->usuarios()->updateExistingPivot($user->id, [
            'estado_solicitud' => $request->estado,
        ]);

        $message = $request->estado === 'aprobado'
            ? 'Solicitud aprobada exitosamente.'
            : 'Solicitud rechazada.';

        return redirect()->back()
            ->with('success', $message);
    }

    /**
     * Mostrar los semilleros del docente/tutor
     */
    public function misSemilleros(): Response
    {
        $user = auth()->user();

        $semilleros = $user->semillerosComoTutor()
            ->with(['coordinador', 'usuarios'])
            ->withCount(['usuarios', 'proyectos'])
            ->get();

        return Inertia::render('Semilleros/MisSemilleros', [
            'semilleros' => $semilleros,
            'auth' => [
                'user' => $user->load('roles')
            ]
        ]);
    }

    /**
     * Mostrar solicitudes de estudiantes para los semilleros del tutor
     */
    public function solicitudes(): Response
    {
        $user = auth()->user();

        $solicitudes = DB::table('semillero_user')
            ->join('semilleros', 'semillero_user.semillero_id', '=', 'semilleros.id')
            ->join('users', 'semillero_user.user_id', '=', 'users.id')
            ->where('semilleros.tutor_id', $user->id)
            ->where('semillero_user.estado_solicitud', 'pendiente')
            ->select(
                'semillero_user.*',
                'semilleros.nombre as semillero_nombre',
                'users.name as estudiante_nombre',
                'users.email as estudiante_email'
            )
            ->get();

        return Inertia::render('Semilleros/Solicitudes', [
            'solicitudes' => $solicitudes,
        ]);
    }
}
