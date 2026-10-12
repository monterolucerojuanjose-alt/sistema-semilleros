<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * ============================================================================
 * CONTROLADOR DE VERIFICACIÓN DE CORREO
 * ============================================================================
 *
 * Atiende el enlace que el usuario recibe por correo tras registrarse y marca
 * su dirección como verificada.
 *
 * Es un controlador «invocable»: tiene un único método __invoke, porque
 * atiende una sola acción y no el conjunto habitual de index/create/store.
 */
class VerifyEmailController extends Controller
{
    /**
     * Marca como verificado el correo del usuario.
     *
     * @param  EmailVerificationRequest  $request  Petición que ya ha comprobado
     *         que la firma del enlace es válida y no ha caducado. Esa validación
     *         es lo que impide que alguien verifique un correo ajeno cambiando
     *         el identificador de la dirección.
     * @return RedirectResponse Redirección al panel con el aviso de verificado.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Si el correo ya estaba verificado no se hace nada más. Cubre el caso
        // de que el usuario pulse dos veces el enlace del correo.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // markEmailAsVerified devuelve verdadero solo si realmente cambió el
        // estado, de modo que el evento se dispara una única vez.
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // El parámetro «verified=1» es lo que la pantalla lee para mostrar el
        // mensaje de confirmación al usuario.
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
