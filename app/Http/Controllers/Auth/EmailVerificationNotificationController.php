<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ============================================================================
 * CONTROLADOR DE REENVÍO DEL CORREO DE VERIFICACIÓN
 * ============================================================================
 *
 * Vuelve a enviar el correo de verificación cuando el usuario no lo recibió
 * o lo perdió, desde el botón «Reenviar» de la pantalla de aviso.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Reenvía el correo de verificación.
     *
     * @param  Request  $request  Petición entrante.
     * @return RedirectResponse Vuelve a la pantalla anterior con el aviso de envío.
     */
    public function store(Request $request): RedirectResponse
    {
        // Si el correo ya está verificado no se reenvía nada: evita generar
        // correos inútiles si el usuario pulsa el botón por costumbre.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        // La pantalla lee este valor de sesión para confirmar el envío.
        return back()->with('status', 'verification-link-sent');
    }
}
