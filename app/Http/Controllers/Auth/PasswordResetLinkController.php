<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DE ENVÍO DEL ENLACE DE RECUPERACIÓN
 * ============================================================================
 *
 * Abre el proceso de «olvidé mi contraseña»: recoge el correo del usuario y
 * le envía un enlace con un token de un solo uso.
 *
 * El proceso se completa después en NewPasswordController, cuando el usuario
 * pulsa ese enlace.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Muestra el formulario donde se pide el correo.
     *
     * @return Response La pantalla Auth/ForgotPassword renderizada con Inertia.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Envía el enlace de recuperación al correo indicado.
     *
     * @param  Request  $request  Petición con el correo del usuario.
     * @return RedirectResponse Vuelve al formulario con el aviso de envío.
     *
     * @throws ValidationException Si el correo no está registrado o se pidió
     *                             el enlace demasiadas veces seguidas.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Genera el token, lo guarda en la tabla password_reset_tokens y envía
        // el correo. Laravel limita la frecuencia de estas peticiones para que
        // nadie pueda usar el formulario para inundar de correos a un tercero.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
