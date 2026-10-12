<?php

namespace App\Listeners;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Deja constancia en la auditoría de los accesos al sistema.
 *
 * HU-07 pide que el administrador pueda "dar seguimiento a las acciones
 * realizadas por los usuarios"; el inicio y cierre de sesión son la acción
 * base de ese seguimiento y son, además, el registro que permite detectar
 * intentos de acceso no autorizados.
 */
class RegistrarAuditoriaAutenticacion
{
    public function inicioSesion(Login $event): void
    {
        Auditoria::registrar(
            $event->user->id,
            'login',
            'Sesión',
            $event->user->id,
            "Inicio de sesión de {$event->user->email}"
        );
    }

    public function cierreSesion(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        Auditoria::registrar(
            $event->user->id,
            'logout',
            'Sesión',
            $event->user->id,
            "Cierre de sesión de {$event->user->email}"
        );
    }

    public function intentoFallido(Failed $event): void
    {
        $correo = $event->credentials['email'] ?? 'desconocido';

        Auditoria::registrar(
            $event->user?->id,
            'login_fallido',
            'Sesión',
            $event->user?->id,
            $event->user
                ? "Contraseña incorrecta para {$correo}"
                : "Intento de acceso con un correo no registrado: {$correo}"
        );
    }

    public function bloqueo(Lockout $event): void
    {
        $correo = $event->request->input('email', 'desconocido');
        $usuario = User::where('email', $correo)->first();

        Auditoria::registrar(
            $usuario?->id,
            'bloqueo',
            'Sesión',
            $usuario?->id,
            "Acceso bloqueado temporalmente por exceso de intentos fallidos: {$correo}"
        );
    }

    /**
     * Suscribir los manejadores a los eventos de autenticación de Laravel.
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'inicioSesion',
            Logout::class => 'cierreSesion',
            Failed::class => 'intentoFallido',
            Lockout::class => 'bloqueo',
        ];
    }
}
