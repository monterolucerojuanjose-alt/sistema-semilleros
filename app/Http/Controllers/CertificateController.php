<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Evento;
use App\Support\RecursosPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Administrador y Coordinador gestionan todos los certificados. */
    private function esGestor(): bool
    {
        return auth()->user()->hasAnyRole(['Administrador', 'Coordinador']);
    }

    /**
     * Muestra el listado de registros.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $filtros = $request->only(['buscar', 'tipo', 'estado', 'fecha_inicio', 'fecha_fin']);

        // «mis-certificados» siempre muestra sólo los propios, también a un
        // gestor; «certificados» muestra todos a los gestores.
        $routeName = $request->route()?->getName() ?? 'certificados.index';
        $soloPropios = $routeName === 'mis-certificados' || !$this->esGestor();

        $query = Certificate::with(['usuario:id,name,email', 'evento:id,titulo', 'emisor:id,name'])
            ->when($soloPropios, fn ($q) => $q->porUsuario($user->id))
            ->buscar($filtros['buscar'] ?? '')
            ->porTipo($filtros['tipo'] ?? '')
            ->porEstado($filtros['estado'] ?? '')
            ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);

        // Totales del encabezado, sobre el mismo universo que ve el usuario.
        $base = Certificate::query()->when($soloPropios, fn ($q) => $q->porUsuario($user->id));
        $resumen = [
            'total' => (clone $base)->count(),
            'vigentes' => (clone $base)->where('estado', 'activo')->count(),
            'revocados' => (clone $base)->where('estado', 'revocado')->count(),
            'este_anio' => (clone $base)->whereYear('fecha_emision', now()->year)->count(),
        ];

        $certificados = $query->orderBy('fecha_emision', 'desc')->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render($routeName === 'mis-certificados' ? 'Certificados/MisCertificados' : 'Certificados/Index', [
            'certificados' => $certificados,
            'tipos' => Certificate::TIPOS,
            'estados' => Certificate::ESTADOS,
            'filtros' => $filtros,
            'resumen' => $resumen,
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        abort_unless($this->esGestor(), 403, 'No tienes permisos para crear certificados.');

        return Inertia::render('Certificados/Create', [
            'estudiantes' => User::role('Estudiante')->select('id', 'name', 'email')->orderBy('name')->get(),
            'eventos' => Evento::select('id', 'titulo', 'fecha_inicio')->orderByDesc('fecha_inicio')->get(),
            'tipos' => Certificate::TIPOS,
        ]);
    }

    /**
     * Guarda un registro nuevo en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->esGestor(), 403, 'No tienes permisos para crear certificados.');

        $validated = $this->validar($request);

        $certificado = Certificate::create([
            'codigo' => Certificate::generarCodigo(),
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo' => $validated['tipo'],
            'fecha_emision' => $validated['fecha_emision'],
            'user_id' => $validated['user_id'],
            'evento_id' => $validated['evento_id'] ?? null,
            'emitido_por' => auth()->id(),
            'metadata' => $this->metadata($validated),
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => 'activo',
            'archivo_certificado' => $request->hasFile('archivo_certificado')
                ? $request->file('archivo_certificado')->store('certificados', 'public')
                : null,
        ]);

        return redirect()->route('certificados.show', $certificado)
            ->with('success', 'Certificado emitido correctamente.');
    }

    /**
     * Muestra el detalle de un registro concreto.
     */
    public function show(Certificate $certificado): Response
    {
        $this->autorizarLectura($certificado);

        // Usuario y emisor completos: Avatar necesita la foto (avatar_url).
        $certificado->load(['usuario', 'evento:id,titulo,descripcion,fecha_inicio,fecha_fin', 'emisor']);

        return Inertia::render('Certificados/Show', [
            'certificado' => $certificado,
            'tipos' => Certificate::TIPOS,
            'estados' => Certificate::ESTADOS,
            'puedeGestionar' => $this->esGestor() || $certificado->emitido_por === auth()->id(),
            'tieneArchivoPropio' => $this->archivoPropioDisponible($certificado),
        ]);
    }

    /**
     * Muestra el formulario de edicion.
     */
    public function edit(Certificate $certificado): Response
    {
        $this->autorizarGestion($certificado);

        $certificado->load(['usuario:id,name,email', 'evento:id,titulo']);

        return Inertia::render('Certificados/Edit', [
            'certificado' => $certificado,
            'estudiantes' => User::role('Estudiante')->select('id', 'name', 'email')->orderBy('name')->get(),
            'eventos' => Evento::select('id', 'titulo', 'fecha_inicio')->orderByDesc('fecha_inicio')->get(),
            'tipos' => Certificate::TIPOS,
            'estados' => Certificate::ESTADOS,
        ]);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Request $request, Certificate $certificado): RedirectResponse
    {
        $this->autorizarGestion($certificado);

        $validated = $this->validar($request, conEstado: true);

        $datos = [
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo' => $validated['tipo'],
            'fecha_emision' => $validated['fecha_emision'],
            'user_id' => $validated['user_id'],
            'evento_id' => $validated['evento_id'] ?? null,
            'estado' => $validated['estado'],
            'observaciones' => $validated['observaciones'] ?? null,
            'metadata' => array_merge($certificado->metadata ?? [], $this->metadata($validated)),
        ];

        // Un archivo nuevo reemplaza al anterior; quitarlo vuelve al
        // certificado generado por el sistema.
        if ($request->hasFile('archivo_certificado') || $request->boolean('quitar_archivo')) {
            if ($certificado->archivo_certificado) {
                Storage::disk('public')->delete($certificado->archivo_certificado);
            }
            $datos['archivo_certificado'] = $request->hasFile('archivo_certificado')
                ? $request->file('archivo_certificado')->store('certificados', 'public')
                : null;
        }

        $certificado->update($datos);

        return redirect()->route('certificados.show', $certificado)
            ->with('success', 'Certificado actualizado correctamente.');
    }

    /**
     * Elimina un registro de la base de datos.
     */
    public function destroy(Certificate $certificado): RedirectResponse
    {
        $this->autorizarGestion($certificado);

        if ($certificado->archivo_certificado) {
            Storage::disk('public')->delete($certificado->archivo_certificado);
        }

        $certificado->delete();

        return redirect()->route('certificados.index')
            ->with('success', 'Certificado eliminado correctamente.');
    }

    /**
     * Entrega el PDF del certificado.
     *
     * Si al emitirlo se adjuntó un PDF propio (por ejemplo, uno ya firmado a
     * mano), se entrega ese. Si no, el sistema genera el certificado con el
     * formato institucional. Antes, sin archivo adjunto, no había nada que
     * descargar y la pantalla mostraba «Sin PDF».
     *
     * Con ?ver=1 se abre en el navegador en lugar de descargarse.
     */
    public function descargar(Request $request, Certificate $certificado)
    {
        $this->autorizarLectura($certificado);

        // Un certificado revocado no debe circular como válido: su titular ya
        // no puede descargarlo; los gestores sí, marcado como «REVOCADO».
        if ($certificado->estado === 'revocado' && !$this->esGestor()) {
            return redirect()->back()->with('error', 'Este certificado fue revocado y ya no está disponible.');
        }

        $nombre = 'certificado_' . $certificado->codigo . '.pdf';
        $enLinea = $request->boolean('ver');

        if ($this->archivoPropioDisponible($certificado)) {
            $ruta = Storage::disk('public')->path($certificado->archivo_certificado);

            return $enLinea
                ? response()->file($ruta, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $nombre . '"'])
                : response()->download($ruta, $nombre);
        }

        $certificado->loadMissing(['usuario:id,name', 'evento:id,titulo', 'emisor']);

        $pdf = Pdf::loadView('certificados.pdf', [
            'certificado' => $certificado,
            'logo' => RecursosPdf::logo(),
        ])->setPaper('A4', 'landscape');

        return $enLinea ? $pdf->stream($nombre) : $pdf->download($nombre);
    }

    /* ------------------------------------------------------------------ */

    private function validar(Request $request, bool $conEstado = false): array
    {
        return $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => ['required', Rule::in(array_keys(Certificate::TIPOS))],
            'fecha_emision' => 'required|date|before_or_equal:today',
            'user_id' => 'required|exists:users,id',
            'evento_id' => 'nullable|exists:eventos,id',
            'horas' => 'nullable|integer|min:1|max:1000',
            'observaciones' => 'nullable|string|max:1000',
            'estado' => $conEstado ? ['required', Rule::in(array_keys(Certificate::ESTADOS))] : 'prohibited',
            'archivo_certificado' => 'nullable|file|mimes:pdf|max:10240',
            'quitar_archivo' => 'nullable|boolean',
        ], [
            'titulo.required' => 'El título del certificado es obligatorio.',
            'tipo.required' => 'El tipo de certificado es obligatorio.',
            'tipo.in' => 'El tipo de certificado no es válido.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura.',
            'user_id.required' => 'Debes seleccionar un estudiante.',
            'user_id.exists' => 'El estudiante seleccionado no existe.',
            'evento_id.exists' => 'El evento seleccionado no existe.',
            'horas.integer' => 'La intensidad horaria debe ser un número entero.',
            'horas.min' => 'La intensidad horaria debe ser de al menos 1 hora.',
            'horas.max' => 'La intensidad horaria no puede superar las 1000 horas.',
            'estado.required' => 'El estado del certificado es obligatorio.',
            'archivo_certificado.file' => 'El archivo debe ser válido.',
            'archivo_certificado.mimes' => 'El archivo debe ser un PDF.',
            'archivo_certificado.max' => 'El archivo no puede pesar más de 10 MB.',
        ]);
    }

    /** Datos adicionales que se guardan en la columna metadata. */
    private function metadata(array $validated): array
    {
        return ['horas' => isset($validated['horas']) ? (int) $validated['horas'] : null];
    }

    /** El archivo adjunto existe de verdad en el disco (no sólo en la base). */
    private function archivoPropioDisponible(Certificate $certificado): bool
    {
        return $certificado->archivo_certificado
            && Storage::disk('public')->exists($certificado->archivo_certificado);
    }

    /** Puede verlo su titular, quien lo emitió o un gestor. */
    private function autorizarLectura(Certificate $certificado): void
    {
        $user = auth()->user();

        if (!$this->esGestor() && $certificado->user_id !== $user->id && $certificado->emitido_por !== $user->id) {
            abort(403, 'No tienes permisos para ver este certificado.');
        }
    }

    /** Puede modificarlo quien lo emitió o un gestor. */
    private function autorizarGestion(Certificate $certificado): void
    {
        if (!$this->esGestor() && $certificado->emitido_por !== auth()->id()) {
            abort(403, 'No tienes permisos para modificar este certificado.');
        }
    }
}
