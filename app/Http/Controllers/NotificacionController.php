<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificacionController extends Controller
{
    /**
     * Sólo el administrador emite anuncios (HU-09: "Como administrador,
     * quiero enviar notificaciones individuales, masivas o por rol").
     * Recibirlos y gestionarlos, en cambio, es de todos los usuarios (HU-37, HU-38).
     */
    private const ROLES_EMISORES = ['Administrador'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Formulario para enviar anuncios (HU-09).
     */
    public function create(): Response
    {
        $this->autorizarEnvio();

        $roles = ['Administrador', 'Coordinador', 'Docente/Tutor', 'Estudiante', 'Jurado'];

        $usuarios = User::select('id', 'name', 'email')
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn ($usuario) => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->roles->pluck('name')->first() ?? 'Sin rol',
            ]);

        return Inertia::render('Notificaciones/Crear', [
            'auth' => ['user' => auth()->user()->load('roles')],
            'roles' => $roles,
            'usuarios' => $usuarios,
            // Permite mostrar cuántas personas recibirán el anuncio antes de enviarlo.
            'conteoPorRol' => collect($roles)
                ->mapWithKeys(fn ($rol) => [
                    $rol => User::whereHas('roles', fn ($q) => $q->where('name', $rol))->count(),
                ]),
            'totalUsuarios' => User::count(),
        ]);
    }

    /**
     * Enviar un anuncio a todos los usuarios, a un rol o a usuarios concretos.
     *
     * Sustituye a los tres endpoints anteriores (store, storeMultiple y
     * storeForRole), que además no comprobaban el rol de quien los invocaba.
     */
    public function enviar(Request $request): RedirectResponse
    {
        $this->autorizarEnvio();

        $datos = $request->validate([
            'modo' => ['required', Rule::in(['todos', 'rol', 'usuarios'])],
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string|max:2000',
            'tipo' => ['required', Rule::in(['info', 'alerta', 'exito', 'error'])],
            // exclude_unless descarta el campo cuando no corresponde al modo
            // elegido. Con "nullable" no basta: un arreglo vacío sí está
            // presente y la regla min:1 saltaría aunque el envío sea por rol.
            'rol' => 'exclude_unless:modo,rol|required|string|exists:roles,name',
            'usuario_ids' => 'exclude_unless:modo,usuarios|required|array|min:1',
            'usuario_ids.*' => 'exists:users,id',
        ], [
            'rol.required' => 'Selecciona el rol que recibirá el anuncio.',
            'usuario_ids.required' => 'Selecciona al menos un destinatario.',
            'usuario_ids.min' => 'Selecciona al menos un destinatario.',
        ]);

        $destinatarios = $this->resolverDestinatarios($datos);

        if ($destinatarios->isEmpty()) {
            return back()->withErrors([
                'destinatarios' => 'No hay usuarios que coincidan con los destinatarios elegidos.',
            ])->withInput();
        }

        $enviadas = Notificacion::crearParaUsuarios(
            $destinatarios,
            $datos['titulo'],
            $datos['mensaje'],
            $datos['tipo'],
            null,
            auth()->id()
        );

        return redirect()
            ->route('notificaciones.index')
            ->with('exito', "Anuncio enviado a {$enviadas} " . ($enviadas === 1 ? 'usuario' : 'usuarios') . '.');
    }

    /**
     * Identificadores de quienes deben recibir el anuncio.
     */
    private function resolverDestinatarios(array $datos)
    {
        return match ($datos['modo']) {
            // "Todos" incluye a todo el mundo: antes se enviaba únicamente a
            // Estudiante, Docente/Tutor y Jurado, dejando fuera a los demás.
            'todos' => User::pluck('id'),
            'rol' => User::whereHas('roles', fn ($q) => $q->where('name', $datos['rol']))->pluck('id'),
            'usuarios' => collect($datos['usuario_ids']),
        };
    }

    /**
     * Bandeja de notificaciones del usuario autenticado (HU-37).
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $filtros = $request->only(['tipo', 'estado', 'periodo']);

        $query = Notificacion::porUsuario($user->id)->with('remitente:id,name');

        if (!empty($filtros['tipo'])) {
            $query->porTipo($filtros['tipo']);
        }

        if (($filtros['estado'] ?? null) === 'no_leidas') {
            $query->noLeidas();
        } elseif (($filtros['estado'] ?? null) === 'leidas') {
            $query->leidas();
        }

        // Permite que el indicador "Recibidas hoy" filtre al pulsarlo.
        if (($filtros['periodo'] ?? null) === 'hoy') {
            $query->whereDate('created_at', today());
        }

        $notificaciones = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $propias = Notificacion::porUsuario($user->id);

        return Inertia::render('Notificaciones/Index', [
            'auth' => ['user' => $user->load('roles')],
            'notificaciones' => $notificaciones,
            'filtros' => $filtros,
            'estadisticas' => [
                'total' => (clone $propias)->count(),
                'no_leidas' => (clone $propias)->noLeidas()->count(),
                'leidas' => (clone $propias)->leidas()->count(),
                'hoy' => (clone $propias)->whereDate('created_at', today())->count(),
            ],
            'puedeEnviar' => $user->hasAnyRole(self::ROLES_EMISORES),
        ]);
    }

    /**
     * Notificaciones sin leer, para el indicador de la barra superior.
     */
    public function noLeidas(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'notificaciones' => Notificacion::porUsuario($user->id)
                ->noLeidas()
                ->with('remitente:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'total_no_leidas' => Notificacion::porUsuario($user->id)->noLeidas()->count(),
        ]);
    }

    /**
     * Marcar una notificación como leída (HU-38).
     */
    public function markAsRead($id): JsonResponse
    {
        $notificacion = Notificacion::porUsuario(auth()->id())->findOrFail($id);
        $notificacion->marcarComoLeida();

        return response()->json(['success' => true, 'mensaje' => 'Notificación marcada como leída']);
    }

    /**
     * Marcar todas como leídas (HU-38).
     */
    public function markAllAsRead(): JsonResponse
    {
        $total = Notificacion::porUsuario(auth()->id())
            ->noLeidas()
            ->update(['leida' => true, 'leida_en' => now()]);

        return response()->json([
            'success' => true,
            'mensaje' => $total > 0
                ? "Se marcaron {$total} notificaciones como leídas"
                : 'No había notificaciones sin leer',
            'total' => $total,
        ]);
    }

    /**
     * Eliminar una notificación de la bandeja propia.
     */
    public function destroy($id): JsonResponse
    {
        Notificacion::porUsuario(auth()->id())->findOrFail($id)->delete();

        return response()->json(['success' => true, 'mensaje' => 'Notificación eliminada']);
    }

    /**
     * Vaciar las notificaciones ya leídas.
     */
    public function destroyRead(): JsonResponse
    {
        $eliminadas = Notificacion::porUsuario(auth()->id())->leidas()->delete();

        return response()->json([
            'success' => true,
            'mensaje' => $eliminadas > 0
                ? "Se eliminaron {$eliminadas} notificaciones leídas"
                : 'No había notificaciones leídas',
            'total_eliminadas' => $eliminadas,
        ]);
    }

    /**
     * Estadísticas de la bandeja del usuario.
     */
    public function stats(): JsonResponse
    {
        $propias = Notificacion::porUsuario(auth()->id());

        return response()->json([
            'total' => (clone $propias)->count(),
            'no_leidas' => (clone $propias)->noLeidas()->count(),
            'leidas' => (clone $propias)->leidas()->count(),
            'por_tipo' => (clone $propias)->selectRaw('tipo, COUNT(*) as total')
                ->groupBy('tipo')
                ->pluck('total', 'tipo'),
        ]);
    }

    /**
     * Sólo Administrador y Coordinador pueden emitir anuncios (HU-09).
     */
    private function autorizarEnvio(): void
    {
        if (! auth()->user()->hasAnyRole(self::ROLES_EMISORES)) {
            abort(403, 'No tienes permisos para enviar notificaciones.');
        }
    }
}
