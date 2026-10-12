<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditoriaExport;

class AuditoriaController extends Controller
{
    use \App\Support\NumeracionPdf;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de registros de auditoría
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        
        // Solo administradores y coordinadores pueden ver auditorías
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a los registros de auditoría.');
        }

        $query = Auditoria::with('usuario:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('entidad')) {
            $query->where('entidad_afectada', $request->entidad);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        $auditorias = $query->paginate(50);

        // Obtener datos para filtros
        $usuarios = \App\Models\User::select('id', 'name', 'email')->orderBy('name')->get();
        $acciones = Auditoria::select('accion')->distinct()->pluck('accion')->sort();
        $entidades = Auditoria::select('entidad_afectada')->distinct()->pluck('entidad_afectada')->sort();

        // Estadísticas globales del registro de auditoría.
        $stats = [
            'total_registros' => Auditoria::count(),
            'actividades_hoy' => Auditoria::whereDate('created_at', today())->count(),
            'actividades_semana' => Auditoria::where('created_at', '>=', now()->subWeek())->count(),
            // Usuarios distintos con actividad registrada en todo el histórico.
            'usuarios_activos' => Auditoria::whereNotNull('usuario_id')->distinct('usuario_id')->count('usuario_id'),
            // Accesos fallidos de la última semana: indicio de intentos no autorizados.
            'accesos_fallidos_semana' => Auditoria::whereIn('accion', ['login_fallido', 'bloqueo'])
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
            'usuarios_mas_activos' => Auditoria::with('usuario:id,name')
                ->selectRaw('usuario_id, COUNT(*) as total')
                ->whereNotNull('usuario_id')
                ->groupBy('usuario_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get(),
            // Distribución por tipo de acción, para el resumen visual.
            'por_accion' => Auditoria::selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->orderByDesc('total')
                ->pluck('total', 'accion'),
        ];

        return Inertia::render('Auditoria/Index', [
            'auth' => [
                'user' => $user->load('roles')
            ],
            'auditorias' => $auditorias,
            'usuarios' => $usuarios,
            'acciones' => $acciones,
            'entidades' => $entidades,
            'stats' => $stats,
            'filters' => $request->only(['usuario_id', 'accion', 'entidad', 'fecha_inicio', 'fecha_fin']),
        ]);
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function stats(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para acceder a las estadísticas de auditoría.');
        }

        $dias = $request->get('dias', 30);
        $resumen = Auditoria::obtenerResumen($dias);

        return response()->json($resumen);
    }

    /**
     * Exportar auditoría en PDF
     */
    public function exportPDF(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para exportar registros de auditoría.');
        }

        $query = Auditoria::with('usuario:id,name,email');

        // Aplicar mismos filtros que en index
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('entidad')) {
            $query->where('entidad_afectada', $request->entidad);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        $auditorias = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('auditoria.pdf', [
            'auditorias' => $auditorias,
            'filtros' => $request->only(['usuario_id', 'accion', 'entidad', 'fecha_inicio', 'fecha_fin']),
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('A4', 'landscape');

        $nombreArchivo = 'auditoria_' . now()->format('Y_m_d_H_i_s') . '.pdf';

        return $this->numerarPaginas($pdf)->download($nombreArchivo);
    }

    /**
     * Exportar auditoría en Excel
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para exportar registros de auditoría.');
        }

        $filtros = $request->only(['usuario_id', 'accion', 'entidad', 'fecha_inicio', 'fecha_fin']);
        $nombreArchivo = 'auditoria_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new AuditoriaExport($filtros), $nombreArchivo);
    }

    /**
     * Obtener detalles de una auditoría específica
     */
    public function show($id): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permisos para ver detalles de auditoría.');
        }

        $auditoria = Auditoria::with('usuario:id,name,email')->findOrFail($id);

        return response()->json($auditoria);
    }

    /**
     * Limpiar registros de auditoría antiguos
     */
    public function cleanup(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['Administrador'])) {
            abort(403, 'Solo los administradores pueden limpiar registros de auditoría.');
        }

        $request->validate([
            'dias' => 'required|integer|min:30|max:365',
        ]);

        $fechaLimite = now()->subDays($request->dias);
        $eliminados = Auditoria::where('created_at', '<', $fechaLimite)->delete();

        // Registrar la acción de limpieza
        Auditoria::registrar(
            $user->id,
            'cleanup',
            'Auditoria',
            null,
            "Limpieza de registros de auditoría anteriores a {$fechaLimite->format('d/m/Y')}",
            null,
            ['registros_eliminados' => $eliminados]
        );

        return response()->json([
            'success' => true,
            'mensaje' => "Se eliminaron {$eliminados} registros de auditoría anteriores a {$fechaLimite->format('d/m/Y')}",
            'registros_eliminados' => $eliminados,
        ]);
    }
}