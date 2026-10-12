<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Plantilla base que se carga en la primera visita.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determina la versión actual de los archivos compilados.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Datos que se envian a todas las pantallas de forma automatica.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles') : null,
            ],
            // Mensajes de una sola vez, para confirmar acciones en la interfaz.
            //
            // Los controladores guardan el aviso de éxito como 'success' (34
            // sitios) y sólo uno usa 'exito'. Antes aquí se compartía nada más
            // 'exito', así que TODOS los «Solicitud enviada», «Proyecto creado»,
            // etc. se perdían: la acción se hacía pero la pantalla no decía nada
            // y parecía que no había funcionado. Se exponen las dos claves.
            'flash' => [
                'success' => fn () => $request->session()->get('success')
                    ?? $request->session()->get('exito'),
                'exito' => fn () => $request->session()->get('exito')
                    ?? $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            // Avisos pendientes, para el distintivo del menú lateral. Se evalúa
            // de forma diferida para no consultarlo en peticiones que no lo usan.
            'notificacionesNoLeidas' => fn () => $request->user()
                ? \App\Models\Notificacion::where('usuario_id', $request->user()->id)
                    ->where('leida', false)
                    ->count()
                : 0,
        ];
    }
}
