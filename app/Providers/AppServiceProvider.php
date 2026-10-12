<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Certificado;
use App\Models\Semillero;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Evento;
use App\Models\InscripcionEvento;
use App\Models\EventoParticipante;
use App\Models\Certificate;
use App\Models\Evaluacion;
use App\Models\Rubrica;
use App\Models\VersionProyecto;
use App\Policies\SemilleroPolicy;
use App\Listeners\RegistrarAuditoriaAutenticacion;
use App\Observers\AuditoriaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Arranca los servicios de la aplicación.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Registrar políticas
        Gate::policy(Semillero::class, SemilleroPolicy::class);

        // Auditoría automática de las entidades del sistema (HU-07).
        // Se cubren todos los módulos descritos en la tesis; se dejan fuera
        // Auditoria (provocaría recursión), Notificacion (genera ruido) y las
        // tablas de detalle, cuyos cambios ya quedan registrados en su entidad padre.
        User::observe(AuditoriaObserver::class);
        Semillero::observe(AuditoriaObserver::class);
        Proyecto::observe(AuditoriaObserver::class);
        VersionProyecto::observe(AuditoriaObserver::class);
        Evento::observe(AuditoriaObserver::class);
        InscripcionEvento::observe(AuditoriaObserver::class);
        EventoParticipante::observe(AuditoriaObserver::class);
        Rubrica::observe(AuditoriaObserver::class);
        Evaluacion::observe(AuditoriaObserver::class);
        Certificate::observe(AuditoriaObserver::class);
        Certificado::observe(AuditoriaObserver::class);

        // Auditoría de accesos al sistema (inicio y cierre de sesión,
        // intentos fallidos y bloqueos por exceso de intentos).
        Event::subscribe(RegistrarAuditoriaAutenticacion::class);
    }
}
