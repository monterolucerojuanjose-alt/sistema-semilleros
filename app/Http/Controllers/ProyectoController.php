<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use App\Models\Proyecto;
use App\Models\Semillero;
use App\Models\VersionProyecto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $search = $request->get('search', '');
        $estado = $request->get('estado', '');
        $semillero = $request->get('semillero', '');

        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            $query = Proyecto::with(['autor', 'semillero', 'versiones' => function ($query) {
                $query->latest()->limit(1);
            }]);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                        ->orWhere('resumen', 'like', "%{$search}%")
                        ->orWhereHas('autor', function ($authorQuery) use ($search) {
                            $authorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if ($estado) {
                $query->where('estado', $estado);
            }

            if ($semillero) {
                $query->where('semillero_id', $semillero);
            }

            $proyectos = $query->orderByDesc('created_at')->paginate(15);
            $semilleros = Semillero::select('id', 'nombre')->orderBy('nombre')->get();
        } elseif ($user->hasRole('Docente/Tutor')) {
            $semilleroIds = $user->semillerosComoTutor()->pluck('id');
            $proyectos = Proyecto::with(['autor', 'semillero', 'versiones' => function ($query) {
                $query->latest()->limit(1);
            }])
                ->whereIn('semillero_id', $semilleroIds)
                ->orderByDesc('created_at')
                ->paginate(15);
            $semilleros = collect();
        } else {
            $proyectos = Proyecto::with(['autor', 'semillero', 'versiones' => function ($query) {
                $query->latest()->limit(1);
            }])
                ->where('autor_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(15);
            $semilleros = collect();
        }

        return Inertia::render('Proyectos/Index', [
            'proyectos' => $proyectos,
            'semilleros' => $semilleros,
            // Totales por estado sobre TODO lo que el usuario puede ver, sin
            // aplicar los filtros: sirve de panorama fijo mientras se navega
            // entre páginas, que era justo lo que costaba ver de un vistazo.
            'resumen' => $this->resumenPorEstado($user),
            'filters' => [
                'search' => $search,
                'estado' => $estado,
                'semillero' => $semillero,
            ],
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Usuario no autenticado');
        }

        $semilleros = $this->availableSemillerosFor($user);

        // Un estudiante sólo puede registrar proyectos en semilleros donde su
        // solicitud fue aprobada. Si no pertenece a ninguno se le explica antes
        // de llenar el formulario, en lugar de dejarlo enviarlo y rechazarlo.
        $solicitudPendiente = $user->semilleros()
            ->wherePivot('estado_solicitud', 'pendiente')
            ->exists();

        return Inertia::render('Proyectos/Create', [
            'semilleros' => $semilleros,
            'puedeCrear' => $semilleros->isNotEmpty(),
            'solicitudPendiente' => $solicitudPendiente,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function store(StoreProyectoRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $semilleroId = $request->integer('semillero_id');

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            $esMiembro = $user->semilleros()
                ->wherePivot('semillero_id', $semilleroId)
                ->wherePivot('estado_solicitud', 'aprobado')
                ->exists();

            if (!$esMiembro) {
                return redirect()->back()
                    ->with('error', 'No tienes permisos para crear proyectos en este semillero.');
            }
        }

        $proyecto = Proyecto::crearConCodigo([
            'titulo' => $request->string('titulo')->toString(),
            'resumen' => $request->string('resumen')->toString(),
            'semillero_id' => $semilleroId,
            'autor_id' => $user->id,
            'estado' => 'Inscrito',
        ]);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto creado exitosamente.');
    }

    public function show(Proyecto $proyecto): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($user->hasRole('Docente/Tutor')) {
                $esTutor = $user->semillerosComoTutor()->where('id', $proyecto->semillero_id)->exists();
                if (!$esTutor) {
                    abort(403, 'No tienes permisos para ver este proyecto.');
                }
            } elseif ($proyecto->autor_id !== $user->id) {
                abort(403, 'No tienes permisos para ver este proyecto.');
            }
        }

        $proyecto->load(['autor', 'semillero', 'versiones.creador']);
        $proyecto->versiones->each(function ($version) {
            $version->nombre_archivo = $version->nombre_archivo;
            $version->extension = $version->extension;
            $version->tamaño_archivo = $version->tamaño_archivo;
        });

        return Inertia::render('Proyectos/Show', [
            'proyecto' => $proyecto,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function edit(Proyecto $proyecto): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador']) && $proyecto->autor_id !== $user->id) {
            abort(403, 'No tienes permisos para editar este proyecto.');
        }

        $proyecto->load('semillero');

        return Inertia::render('Proyectos/Edit', [
            'proyecto' => $proyecto,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function update(UpdateProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador']) && $proyecto->autor_id !== $user->id) {
            abort(403, 'No tienes permisos para editar este proyecto.');
        }

        $proyecto->update([
            'titulo' => $request->string('titulo')->toString(),
            'resumen' => $request->string('resumen')->toString(),
        ]);

        return redirect()->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador']) && $proyecto->autor_id !== $user->id) {
            abort(403, 'No tienes permisos para eliminar este proyecto.');
        }

        foreach ($proyecto->versiones as $version) {
            if (Storage::exists($version->archivo)) {
                Storage::delete($version->archivo);
            }
        }

        $proyecto->delete();

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto eliminado exitosamente.');
    }

    public function uploadVersion(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador']) && $proyecto->autor_id !== $user->id) {
            abort(403, 'No tienes permisos para subir versiones de este proyecto.');
        }

        $request->validate([
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'comentario' => 'nullable|string|max:500',
        ]);

        $archivo = $request->file('archivo');
        $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
        $rutaArchivo = $archivo->storeAs('proyectos/' . $proyecto->id, $nombreArchivo);

        VersionProyecto::create([
            'proyecto_id' => $proyecto->id,
            'archivo' => $rutaArchivo,
            'comentario' => $request->comentario,
            'creado_por' => $user->id,
        ]);

        return redirect()->back()
            ->with('success', 'Version subida exitosamente.');
    }

    public function history(Proyecto $proyecto): Response
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($user->hasRole('Docente/Tutor')) {
                $esTutor = $user->semillerosComoTutor()->where('id', $proyecto->semillero_id)->exists();
                if (!$esTutor) {
                    abort(403, 'No tienes permisos para ver este proyecto.');
                }
            } elseif ($proyecto->autor_id !== $user->id) {
                abort(403, 'No tienes permisos para ver este proyecto.');
            }
        }

        $versiones = $proyecto->versiones()->with('creador')->orderByDesc('created_at')->get();
        $versiones->each(function ($version) {
            $version->nombre_archivo = $version->nombre_archivo;
            $version->extension = $version->extension;
            $version->tamaño_archivo = $version->tamaño_archivo;
        });

        return Inertia::render('Proyectos/History', [
            'proyecto' => $proyecto,
            'versiones' => $versiones,
            'auth' => [
                'user' => $user->load('roles'),
            ],
        ]);
    }

    public function changeStatus(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para cambiar el estado del proyecto.');
        }

        $request->validate([
            'estado' => 'required|in:Inscrito,En revisión,Aprobado,Rechazado',
        ]);

        $proyecto->update(['estado' => $request->estado]);

        return redirect()->back()
            ->with('success', 'Estado del proyecto actualizado exitosamente.');
    }

    public function downloadVersion(VersionProyecto $version)
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($user->hasRole('Docente/Tutor')) {
                $esTutor = $user->semillerosComoTutor()->where('id', $version->proyecto->semillero_id)->exists();
                if (!$esTutor) {
                    abort(403, 'No tienes permisos para descargar este archivo.');
                }
            } elseif ($version->proyecto->autor_id !== $user->id) {
                abort(403, 'No tienes permisos para descargar este archivo.');
            }
        }

        if (!Storage::exists($version->archivo)) {
            abort(404, 'Archivo no encontrado en el storage.');
        }

        $nombreArchivo = $version->nombre_archivo;
        $rutaCompleta = storage_path('app/' . $version->archivo);

        if (!file_exists($rutaCompleta)) {
            abort(404, 'El archivo no existe en el sistema de archivos.');
        }

        $mimeType = Storage::mimeType($version->archivo);
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            'Content-Length' => filesize($rutaCompleta),
        ];

        return response()->download($rutaCompleta, $nombreArchivo, $headers);
    }

    public function deleteVersion(VersionProyecto $version)
    {
        $user = auth()->user();

        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            if ($user->hasRole('Docente/Tutor')) {
                $esTutor = $user->semillerosComoTutor()->where('id', $version->proyecto->semillero_id)->exists();
                if (!$esTutor) {
                    abort(403, 'No tienes permisos para eliminar esta version.');
                }
            } elseif ($version->proyecto->autor_id !== $user->id) {
                abort(403, 'Solo puedes eliminar versiones de tus propios proyectos.');
            }
        }

        try {
            if (Storage::exists($version->archivo)) {
                Storage::delete($version->archivo);
            }

            $version->delete();

            return redirect()->back()
                ->with('success', 'Version eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la version: ' . $e->getMessage());
        }
    }

    /**
     * Cuántos proyectos hay en cada estado, dentro de lo que el usuario ve.
     *
     * Se respeta el mismo alcance que el listado: el administrador y el
     * coordinador los cuentan todos, el tutor los de sus semilleros y el
     * estudiante solo los suyos.
     */
    private function resumenPorEstado($user): array
    {
        $consulta = Proyecto::query();

        if ($user->hasRole('Docente/Tutor')) {
            $consulta->whereIn('semillero_id', $user->semillerosComoTutor()->pluck('id'));
        } elseif (! $user->hasRole(['Administrador', 'Coordinador'])) {
            $consulta->where('autor_id', $user->id);
        }

        $porEstado = (clone $consulta)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'total' => (int) $porEstado->sum(),
            'inscritos' => (int) ($porEstado['Inscrito'] ?? 0),
            'en_revision' => (int) ($porEstado['En revisión'] ?? 0),
            'aprobados' => (int) ($porEstado['Aprobado'] ?? 0),
            'rechazados' => (int) ($porEstado['Rechazado'] ?? 0),
            // Cuántas personas distintas tienen proyecto: da idea de la
            // participación real, no solo del número de trabajos.
            'autores' => (int) (clone $consulta)->distinct()->count('autor_id'),
        ];
    }

    private function availableSemillerosFor($user)
    {
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            return Semillero::activos()->get();
        }

        $semilleroIds = $user->semilleros()
            ->wherePivot('estado_solicitud', 'aprobado')
            ->pluck('semilleros.id');

        return Semillero::activos()
            ->whereIn('id', $semilleroIds)
            ->get();
    }
}
