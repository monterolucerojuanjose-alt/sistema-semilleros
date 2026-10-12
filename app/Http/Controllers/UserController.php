<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        // La gestión de usuarios es sólo del administrador, salvo «estudiantes»:
        // esa pantalla es la del docente/tutor y su ruta ya la restringe a ese
        // rol. Sin este except, el tutor recibía 403 al pulsar su propio menú.
        $this->middleware('role:Administrador')->except('estudiantes');
    }

    /**
     * Muestra el listado de registros.
     */
    public function index(Request $request): Response
    {
        $query = User::with('roles');

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ordenar
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(15)->withQueryString();

        // Estadísticas por rol
        $roles = Role::all();
        $roleStats = [];
        foreach ($roles as $role) {
            $roleStats[$role->name] = User::role($role->name)->count();
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'roleStats' => $roleStats,
            'filters' => $request->only(['search', 'role', 'estado']),
            'auth' => [
                'user' => auth()->user()->load('roles')
            ]
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        $roles = Role::all();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'estado' => $request->estado,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Muestra el detalle de un registro concreto.
     */
    public function show(User $user): Response
    {
        // Al acotar columnas hay que incluir SIEMPRE la clave con la que
        // Eloquent empareja los registros (tutor_id, autor_id). Sin ella la
        // relación se carga vacía aunque existan filas.
        $user->load([
            'roles',
            'semillerosAprobados:semilleros.id,nombre,estado',
            'semillerosComoTutor:id,nombre,estado,tutor_id',
            'proyectos:id,titulo,codigo,estado,autor_id',
        ]);

        return Inertia::render('Users/Show', [
            'user' => $user,
            // Resumen de actividad, para que la ficha diga algo más que el correo.
            'resumen' => [
                'proyectos' => $user->proyectos()->count(),
                'semilleros' => $user->semillerosAprobados()->count(),
                'semillerosComoTutor' => $user->semillerosComoTutor()->count(),
                'participacionesEventos' => $user->participacionesEventos()->count(),
                'certificados' => $user->certificados()->count(),
            ],
        ]);
    }

    /**
     * Muestra el formulario de edicion.
     */
    public function edit(User $user): Response
    {
        $roles = Role::all();
        $user->load('roles');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'auth' => [
                'user' => auth()->user()->load('roles')
            ]
        ]);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|exists:roles,name',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'estado' => $request->estado,
        ]);

        // Actualizar rol
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina un registro de la base de datos.
     */
    public function destroy(User $user): RedirectResponse
    {
        // No permitir eliminar el propio usuario
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Asignar rol a un usuario
     */
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->assignRole($request->role);

        return redirect()->back()
            ->with('success', 'Rol asignado exitosamente.');
    }

    /**
     * Remover rol de un usuario
     */
    public function removeRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->removeRole($request->role);

        return redirect()->back()
            ->with('success', 'Rol removido exitosamente.');
    }

    /**
     * Obtener usuarios por rol
     */
    public function getByRole(string $role)
    {
        $users = User::role($role)->get();

        return response()->json($users);
    }

    /**
     * Obtener estadísticas de usuarios por rol
     */
    public function getRoleStats()
    {
        $stats = [];
        $roles = Role::all();

        foreach ($roles as $role) {
            $stats[$role->name] = User::role($role->name)->count();
        }

        return response()->json($stats);
    }

    /**
     * Cambiar estado de un usuario
     */
    public function toggleEstado(User $user): RedirectResponse
    {
        // No permitir cambiar el estado del propio usuario
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'No puedes cambiar tu propio estado.');
        }

        $nuevoEstado = $user->estado === 'activo' ? 'inactivo' : 'activo';
        $user->update(['estado' => $nuevoEstado]);

        return redirect()->back()
            ->with('success', "Usuario {$nuevoEstado} exitosamente.");
    }

    /**
     * Resetear contraseña de un usuario
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Contraseña actualizada exitosamente.');
    }

    /**
     * Mostrar estudiantes para docentes/tutores
     */
    public function estudiantes(): Response
    {
        $user = auth()->user();

        // Obtener estudiantes de los semilleros donde el usuario es tutor
        $estudiantes = User::role('Estudiante')
            ->whereHas('semilleros', function($query) use ($user) {
                $query->where('tutor_id', $user->id);
            })
            ->with(['semilleros' => function($query) use ($user) {
                $query->where('tutor_id', $user->id);
            }])
            ->withCount('proyectos')
            ->get();

        return Inertia::render('Users/Estudiantes', [
            'estudiantes' => $estudiantes,
        ]);
    }
}
