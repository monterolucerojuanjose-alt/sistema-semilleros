<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DEL AVISO DE VERIFICACIÓN DE CORREO
 * ============================================================================
 *
 * Muestra la pantalla que pide al usuario revisar su bandeja de entrada.
 * Si el correo ya está verificado, no tiene sentido enseñar ese aviso y se
 * le envía directamente al panel principal.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Decide entre mostrar el aviso o pasar al panel.
     *
     * @param  Request  $request  Petición entrante.
     * @return RedirectResponse|Response Redirección si ya está verificado,
     *                                   o la pantalla de aviso si no lo está.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
