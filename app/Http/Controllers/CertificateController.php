<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra el listado de registros.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Obtener filtros de la request
        $filtros = $request->only(['buscar', 'tipo', 'estado', 'fecha_inicio', 'fecha_fin']);

        // Construir query base
        $query = Certificate::with(['usuario', 'evento', 'emisor']);

        // Aplicar filtros según el rol del usuario
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            // Administradores y coordinadores ven todos los certificados
            $query->buscar($filtros['buscar'] ?? '')
                  ->porTipo($filtros['tipo'] ?? '')
                  ->porEstado($filtros['estado'] ?? '')
                  ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);
        } else {
            // Estudiantes ven solo sus certificados
            $query->porUsuario($user->id)
                  ->buscar($filtros['buscar'] ?? '')
                  ->porTipo($filtros['tipo'] ?? '')
                  ->porEstado($filtros['estado'] ?? '')
                  ->porFecha($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);
        }

        // Ordenar y paginar
        $certificados = $query->orderBy('fecha_emision', 'desc')->paginate(15);

        // Obtener datos adicionales para los filtros
        $tipos = ['participacion', 'ponencia', 'poster', 'organizacion', 'voluntariado'];
        $estados = ['activo', 'revocado'];

        // Determinar qué vista usar según la ruta
        $routeName = request()->route() ? request()->route()->getName() : 'certificados.index';
        $viewName = $routeName === 'mis-certificados' ? 'Certificados/MisCertificados' : 'Certificados/Index';

        return Inertia::render($viewName, [
            'certificados' => $certificados,
            'tipos' => $tipos,
            'estados' => $estados,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para crear certificados.');
        }

        // Obtener estudiantes y eventos para el formulario
        $estudiantes = User::role('Estudiante')->select('id', 'name', 'email')->get();
        $eventos = Evento::activos()->select('id', 'titulo')->get();

        return Inertia::render('Certificados/Create', [
            'estudiantes' => $estudiantes,
            'eventos' => $eventos,
        ]);
    }

    /**
     * Guarda un registro nuevo en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos
        if (!$user->hasRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para crear certificados.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:participacion,ponencia,poster,organizacion,voluntariado',
            'fecha_emision' => 'required|date|before_or_equal:today',
            'user_id' => 'required|exists:users,id',
            'evento_id' => 'nullable|exists:eventos,id',
            'metadata' => 'nullable|array',
            'observaciones' => 'nullable|string|max:1000',
            'archivo_certificado' => 'nullable|file|mimes:pdf|max:10240', // 10MB máximo
        ], [
            'titulo.required' => 'El título del certificado es obligatorio.',
            'tipo.required' => 'El tipo de certificado es obligatorio.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura.',
            'user_id.required' => 'Debes seleccionar un estudiante.',
            'user_id.exists' => 'El estudiante seleccionado no existe.',
            'evento_id.exists' => 'El evento seleccionado no existe.',
            'archivo_certificado.file' => 'El archivo debe ser válido.',
            'archivo_certificado.mimes' => 'El archivo debe ser un PDF.',
            'archivo_certificado.max' => 'El archivo no puede ser mayor a 10MB.',
        ]);

        // Manejar la subida del archivo PDF
        $archivoPath = null;
        if ($request->hasFile('archivo_certificado')) {
            $archivo = $request->file('archivo_certificado');
            $archivoPath = $archivo->store('certificados', 'public');
        }

        // Asegurar que metadata sea un array válido
        $metadata = $validated['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }

        // Crear el certificado
        $certificado = Certificate::create([
            'codigo' => Certificate::generarCodigo(),
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'tipo' => $validated['tipo'],
            'fecha_emision' => $validated['fecha_emision'],
            'user_id' => $validated['user_id'],
            'evento_id' => $validated['evento_id'],
            'emitido_por' => $user->id,
            'metadata' => $metadata,
            'observaciones' => $validated['observaciones'],
            'estado' => 'activo',
            'archivo_certificado' => $archivoPath,
        ]);

        return redirect()->route('certificados.index')
            ->with('success', 'Certificado creado exitosamente.');
    }

    /**
     * Muestra el detalle de un registro concreto.
     */
    public function show(Certificate $certificado): Response
    {
        $user = auth()->user();

        // Verificar permisos - solo el propietario, emisor o admin puede ver
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $certificado->user_id !== $user->id &&
            $certificado->emitido_por !== $user->id) {
            \Log::warning('Acceso denegado a certificado', [
                'user_id' => $user->id,
                'certificado_id' => $certificado->id,
            ]);
            abort(403, 'No tienes permisos para ver este certificado.');
        }

        $certificado->load(['usuario', 'evento', 'emisor']);

        return Inertia::render('Certificados/Show', [
            'certificado' => $certificado,
        ]);
    }

    /**
     * Muestra el formulario de edicion.
     */
    public function edit(Certificate $certificado): Response
    {
        $user = auth()->user();

        // Verificar permisos - solo el emisor o admin puede editar
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $certificado->emitido_por !== $user->id) {
            abort(403, 'No tienes permisos para editar este certificado.');
        }

        $certificado->load(['usuario', 'evento']);
        $estudiantes = User::role('Estudiante')->select('id', 'name', 'email')->get();
        $eventos = Evento::select('id', 'titulo')->get();

        return Inertia::render('Certificados/Edit', [
            'certificado' => $certificado,
            'estudiantes' => $estudiantes,
            'eventos' => $eventos,
        ]);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Request $request, Certificate $certificado): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos - solo el emisor o admin puede editar
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $certificado->emitido_por !== $user->id) {
            abort(403, 'No tienes permisos para editar este certificado.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'required|in:participacion,ponencia,poster,organizacion,voluntariado',
            'fecha_emision' => 'required|date|before_or_equal:today',
            'user_id' => 'required|exists:users,id',
            'evento_id' => 'nullable|exists:eventos,id',
            'metadata' => 'nullable|array',
            'observaciones' => 'nullable|string|max:1000',
            'estado' => 'required|in:activo,revocado',
            'archivo_certificado' => 'nullable|file|mimes:pdf|max:10240', // 10MB máximo
        ], [
            'titulo.required' => 'El título del certificado es obligatorio.',
            'tipo.required' => 'El tipo de certificado es obligatorio.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura.',
            'user_id.required' => 'Debes seleccionar un estudiante.',
            'user_id.exists' => 'El estudiante seleccionado no existe.',
            'evento_id.exists' => 'El evento seleccionado no existe.',
            'estado.required' => 'El estado del certificado es obligatorio.',
            'archivo_certificado.file' => 'El archivo debe ser válido.',
            'archivo_certificado.mimes' => 'El archivo debe ser un PDF.',
            'archivo_certificado.max' => 'El archivo no puede ser mayor a 10MB.',
        ]);

        // Manejar la subida del archivo PDF
        if ($request->hasFile('archivo_certificado')) {
            // Eliminar archivo anterior si existe
            if ($certificado->archivo_certificado) {
                \Storage::disk('public')->delete($certificado->archivo_certificado);
            }

            $archivo = $request->file('archivo_certificado');
            $archivoPath = $archivo->store('certificados', 'public');
            $validated['archivo_certificado'] = $archivoPath;
        }

        // Asegurar que metadata sea un array válido
        $metadata = $validated['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $validated['metadata'] = $metadata;

        $certificado->update($validated);

        return redirect()->route('certificados.index')
            ->with('success', 'Certificado actualizado exitosamente.');
    }

    /**
     * Elimina un registro de la base de datos.
     */
    public function destroy(Certificate $certificado): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos - solo el emisor o admin puede eliminar
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $certificado->emitido_por !== $user->id) {
            abort(403, 'No tienes permisos para eliminar este certificado.');
        }

        // Eliminar archivo si existe
        if ($certificado->archivo_certificado) {
            \Storage::disk('public')->delete($certificado->archivo_certificado);
        }

        $certificado->delete();

        return redirect()->route('certificados.index')
            ->with('success', 'Certificado eliminado exitosamente.');
    }

    /**
     * Descargar el certificado
     */
    public function descargar(Certificate $certificado)
    {
        $user = auth()->user();

        // Verificar permisos - solo el propietario, emisor o admin puede descargar
        if (!$user->hasRole(['Administrador', 'Coordinador']) &&
            $certificado->user_id !== $user->id &&
            $certificado->emitido_por !== $user->id) {
            abort(403, 'No tienes permisos para descargar este certificado.');
        }

        if (!$certificado->archivo_certificado) {
            return redirect()->back()
                ->with('error', 'El archivo del certificado no está disponible.');
        }

        return response()->download(storage_path('app/public/' . $certificado->archivo_certificado));
    }

}
