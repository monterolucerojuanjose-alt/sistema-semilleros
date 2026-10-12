<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * ============================================================================
 * CONTROLADOR DE CAMBIO DE CONTRASEÑA
 * ============================================================================
 *
 * Cambia la contraseña de un usuario que ya tiene la sesión abierta y conoce
 * su contraseña actual, desde la pantalla de perfil.
 *
 * No debe confundirse con NewPasswordController, que restablece la contraseña
 * de alguien que la ha olvidado y se identifica mediante un enlace enviado
 * a su correo.
 */
class PasswordController extends Controller
{
    /**
     * Actualiza la contraseña del usuario autenticado.
     *
     * @param  Request  $request  Contraseña actual y contraseña nueva.
     * @return RedirectResponse Vuelve a la pantalla de perfil.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // La regla «current_password» comprueba que lo escrito coincide con
            // la contraseña guardada. Es la defensa clave de este método: sin
            // ella, bastaría con dejar un ordenador desbloqueado un momento
            // para que un tercero se apropiara de la cuenta.
            'current_password' => ['required', 'current_password'],

            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
