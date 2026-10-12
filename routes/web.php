<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\HistorialInscripcionesController;
use App\Http\Controllers\InscripcionEventoController;
use App\Http\Controllers\MisNotasController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResultadosEvaluacionController;
use App\Http\Controllers\RubricaController;
use App\Http\Controllers\SemilleroController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('semilleros', SemilleroController::class);
    Route::post('semilleros/{semillero}/solicitud', [SemilleroController::class, 'requestJoin'])->name('semilleros.request-join');
    Route::put('semilleros/{semillero}/aprobar/{user}', [SemilleroController::class, 'approveJoin'])->name('semilleros.approve-join');
    // 'Solicitar semillero' se retiró: duplicaba la pantalla de Semilleros,
    // donde el estudiante ya ve el listado y puede pedir su ingreso.

    Route::get('nuevo-proyecto', [ProyectoController::class, 'create'])->name('proyectos.create');
    Route::get('proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
    Route::post('proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
    Route::get('proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('proyectos/{proyecto}/historial', [ProyectoController::class, 'history'])->name('proyectos.history');
    Route::get('proyectos/{proyecto}/edit', [ProyectoController::class, 'edit'])->name('proyectos.edit');
    Route::put('proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('proyectos.update');
    Route::delete('proyectos/{proyecto}', [ProyectoController::class, 'destroy'])->name('proyectos.destroy');
    Route::put('proyectos/{proyecto}/estado', [ProyectoController::class, 'changeStatus'])
        ->middleware('role:Administrador,Coordinador')
        ->name('proyectos.change-status');
    Route::post('proyectos/{proyecto}/version', [ProyectoController::class, 'uploadVersion'])->name('proyectos.upload-version');
    Route::get('versiones/{version}/descargar', [ProyectoController::class, 'downloadVersion'])->name('versiones.download');
    Route::delete('versiones/{version}', [ProyectoController::class, 'deleteVersion'])->name('versiones.delete');

    // parameters() es obligatorio aquí: Route::resource singulariza en inglés y
    // generaba {inscripcione}, que no coincide con el $inscripcion del
    // controlador. Al no coincidir el nombre, Laravel inyectaba un modelo vacío
    // y «ver detalle» reventaba con «property autor_id on null».
    Route::resource('inscripciones', InscripcionEventoController::class)
        ->except(['create', 'store'])
        ->parameters(['inscripciones' => 'inscripcion']);
    Route::get('inscripciones/{inscripcion}/comprobante', [InscripcionEventoController::class, 'comprobante'])->name('inscripciones.comprobante');
    Route::get('mis-inscripciones', [InscripcionEventoController::class, 'index'])->name('mis-inscripciones');

    Route::resource('certificados', CertificateController::class);
    Route::get('certificados/{certificado}/descargar', [CertificateController::class, 'descargar'])->name('certificados.descargar');
    // La verificación por código se retiró: la ruta apuntaba a un componente
    // «Certificados/Verificar» que no existe (el archivo es Verify.vue), así
    // que el botón siempre llevaba a una pantalla rota.
    Route::get('mis-certificados', [CertificateController::class, 'index'])->name('mis-certificados');

    Route::middleware('role:Estudiante')->group(function () {
        Route::get('mis-notas', [MisNotasController::class, 'index'])->name('mis-notas');
        Route::get('mis-notas/{propuesta}', [MisNotasController::class, 'show'])->name('mis-notas.show');
        Route::post('eventos/{evento}/inscribir', [EventoController::class, 'inscribir'])->name('eventos.inscribir');
        Route::post('eventos/{evento}/cancelar-inscripcion', [EventoController::class, 'cancelarInscripcion'])->name('eventos.cancelar-inscripcion');
    });

    Route::middleware('role:Administrador,Coordinador,Docente/Tutor')->group(function () {
        Route::get('resultados-evaluacion', [ResultadosEvaluacionController::class, 'index'])->name('resultados-evaluacion.index');
        Route::get('resultados-evaluacion/{id}', [ResultadosEvaluacionController::class, 'show'])->name('resultados-evaluacion.show');
        Route::get('api/estadisticas-evaluacion', [ResultadosEvaluacionController::class, 'estadisticasGenerales'])->name('resultados-evaluacion.estadisticas');
    });

    Route::middleware('role:Administrador,Coordinador')->group(function () {
        Route::get('historial-inscripciones', [HistorialInscripcionesController::class, 'index'])->name('historial-inscripciones.index');
        Route::get('historial-inscripciones/{id}', [HistorialInscripcionesController::class, 'show'])->name('historial-inscripciones.show');

        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('auditoria/exportar-pdf', [AuditoriaController::class, 'exportPDF'])->name('auditoria.exportar.pdf');
        Route::get('auditoria/exportar-excel', [AuditoriaController::class, 'exportExcel'])->name('auditoria.exportar.excel');
        Route::delete('auditoria/limpiar', [AuditoriaController::class, 'cleanup'])->name('auditoria.cleanup');

        Route::get('eventos/create', [EventoController::class, 'create'])->name('eventos.create');
        Route::post('eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('eventos/{evento}/participantes', [EventoController::class, 'participantes'])->name('eventos.participantes');
        Route::get('eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
        Route::put('eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
        Route::delete('eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');

        Route::get('propuestas/pendientes', [EventoController::class, 'propuestasPendientes'])->name('propuestas.pendientes');
        Route::get('propuestas/{participante}', [EventoController::class, 'verPropuesta'])->name('propuestas.ver');
        Route::post('propuestas/{participante}/aprobar', [EventoController::class, 'aprobarPropuesta'])->name('propuestas.aprobar');
        Route::post('propuestas/{participante}/rechazar', [EventoController::class, 'rechazarPropuesta'])->name('propuestas.rechazar');
        Route::get('eventos/evaluadores', [EventoController::class, 'getEvaluadoresDisponibles'])->name('eventos.evaluadores');
    });

    Route::resource('rubricas', RubricaController::class);
    Route::post('rubricas/{rubrica}/duplicar', [RubricaController::class, 'duplicar'])->name('rubricas.duplicar');

    // IMPORTANTE: las rutas con nombre fijo (ranking, create, ...) deben ir
    // ANTES de evaluaciones/{evaluacion}. Si no, el comodín captura la palabra
    // como si fuera un id y la página responde 404.
    Route::get('evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');
    Route::get('evaluaciones/proyectos-asignados', [EvaluacionController::class, 'proyectosAsignados'])->name('evaluaciones.proyectos-asignados');
    Route::get('evaluaciones/create', [EvaluacionController::class, 'create'])->name('evaluaciones.create');
    Route::get('evaluaciones/ranking', [EvaluacionController::class, 'ranking'])->name('evaluaciones.ranking');
    Route::get('evaluaciones/proyecto/{id}/resultados', [EvaluacionController::class, 'projectResults'])->name('evaluaciones.project-results');
    Route::get('evaluaciones/jurado/{id}', [EvaluacionController::class, 'juradoEvaluations'])->name('evaluaciones.jurado');
    Route::post('evaluaciones', [EvaluacionController::class, 'store'])->name('evaluaciones.store');
    Route::get('evaluaciones/{evaluacion}', [EvaluacionController::class, 'show'])->name('evaluaciones.show');

    Route::get('api/dashboard/resumen-general', [DashboardController::class, 'getResumenGeneral'])->name('dashboard.resumen-general');
    Route::get('api/dashboard/proyectos-por-estado', [DashboardController::class, 'getProyectosPorEstado'])->name('dashboard.proyectos-por-estado');
    Route::get('api/dashboard/proyectos-por-semillero', [DashboardController::class, 'getProyectosPorSemillero'])->name('dashboard.proyectos-por-semillero');
    Route::get('api/dashboard/ranking-proyectos', [DashboardController::class, 'getRankingProyectos'])->name('dashboard.ranking-proyectos');
    Route::get('api/dashboard/participacion-por-rol', [DashboardController::class, 'getParticipacionPorRol'])->name('dashboard.participacion-por-rol');
    Route::get('api/dashboard/evaluaciones-por-rubrica', [DashboardController::class, 'getEvaluacionesPorRubrica'])->name('dashboard.evaluaciones-por-rubrica');
    Route::get('api/dashboard/inscripciones-por-tipo', [DashboardController::class, 'getInscripcionesPorTipo'])->name('dashboard.inscripciones-por-tipo');
    Route::get('api/dashboard/estadisticas-mensuales', [DashboardController::class, 'getEstadisticasMensuales'])->name('dashboard.estadisticas-mensuales');

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::post('reportes/exportar', [ReporteController::class, 'exportarPDF'])->name('reportes.exportar');
    Route::get('reportes/preview', [ReporteController::class, 'preview'])->name('reportes.preview');

    // Bandeja propia: cualquier usuario autenticado gestiona sus notificaciones.
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::put('notificaciones/marcar-todas-leidas', [NotificacionController::class, 'markAllAsRead'])->name('notificaciones.mark-all-read');
    Route::put('notificaciones/{id}/leida', [NotificacionController::class, 'markAsRead'])->name('notificaciones.mark-read');
    Route::delete('notificaciones/eliminar-leidas', [NotificacionController::class, 'destroyRead'])->name('notificaciones.destroy-read');
    Route::delete('notificaciones/{id}', [NotificacionController::class, 'destroy'])->name('notificaciones.destroy');

    // Emitir anuncios está restringido al Administrador (HU-09). Recibirlas y
    // gestionarlas es de todos los usuarios autenticados (HU-37 y HU-38).
    Route::middleware('role:Administrador')->group(function () {
        Route::get('notificaciones/crear', [NotificacionController::class, 'create'])->name('notificaciones.create');
        Route::post('notificaciones/enviar', [NotificacionController::class, 'enviar'])->name('notificaciones.enviar');
    });

    Route::prefix('api')->group(function () {
        Route::get('notificaciones/no-leidas', [NotificacionController::class, 'noLeidas'])->name('api.notificaciones.no-leidas');
        Route::get('notificaciones/stats', [NotificacionController::class, 'stats'])->name('api.notificaciones.stats');

        Route::middleware('role:Administrador,Coordinador')->group(function () {
            Route::get('auditoria/stats', [AuditoriaController::class, 'stats'])->name('api.auditoria.stats');
            Route::get('auditoria/{id}', [AuditoriaController::class, 'show'])->name('api.auditoria.show');
        });
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
        Route::delete('users/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.remove-role');
        Route::post('users/{user}/toggle-estado', [UserController::class, 'toggleEstado'])->name('users.toggle-estado');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('users/role/{role}', [UserController::class, 'getByRole'])->name('users.by-role');
        Route::get('users/stats/roles', [UserController::class, 'getRoleStats'])->name('users.role-stats');
    });

    Route::middleware('role:Docente/Tutor')->group(function () {
        Route::get('mis-semilleros', [SemilleroController::class, 'misSemilleros'])->name('mis-semilleros');
        Route::get('estudiantes', [UserController::class, 'estudiantes'])->name('estudiantes');
        Route::get('solicitudes', [SemilleroController::class, 'solicitudes'])->name('solicitudes');
    });

    Route::middleware('role:Jurado,Docente/Tutor')->group(function () {
        Route::get('mis-proyectos-evaluar', [EventoController::class, 'proyectosAsignados'])->name('proyectos.asignados');
        Route::get('proyectos/{participante}/evaluar', [EventoController::class, 'mostrarFormularioEvaluacion'])->name('proyectos.formulario-evaluacion');
        Route::post('proyectos/{participante}/evaluar', [EventoController::class, 'evaluarProyecto'])->name('proyectos.evaluar');
    });

    Route::get('eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
});

require __DIR__ . '/auth.php';
