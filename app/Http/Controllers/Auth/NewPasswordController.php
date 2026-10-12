<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DE RESTABLECIMIENTO DE CONTRASEÑA
 * ============================================================================
 *
 * Cierra el proceso de «olvidé mi contraseña»: recibe al usuario que llega
 * desde el enlace del correo y le permite fijar una contraseña nueva sin
 * conocer la anterior.
 *
 * La identidad no se comprueba con la contraseña antigua (que el usuario ha
 * olvidado), sino con un token de un solo uso que viaja en el enlace y que
 * solo puede tener quien accede a esa cuenta de correo.
 */
class NewPasswordController extends Controller
{
    /**
     * Muestra el formulario de nueva contraseña.
     *
     * @param  Request  $request  Petición con el correo y el token del enlace.
     * @return Response La pantalla Auth/ResetPassword renderizada con Inertia.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,

            // El token viaja en la dirección del enlace, no en un campo del
            // formulario. Se pasa a la pantalla para que lo devuelva oculto
            // al enviar la nueva contraseña.
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Guarda la nueva contraseña si el token es válido.
     *
     * @param  Request  $request  Correo, token y contraseña nueva.
     * @return RedirectResponse Redirección al inicio de sesión.
     *
     * @throws ValidationException Si el token caducó, ya se usó o no corresponde.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Password::reset comprueba que el token corresponde a ese correo y que
        // no ha caducado. Solo si es correcto ejecuta la función de abajo.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),

                    // Cambiar el «remember_token» invalida las sesiones que
                    // quedaran abiertas con la opción «recordarme». Es
                    // importante: si alguien accedió a la cuenta, restablecer
                    // la contraseña también lo expulsa de esos dispositivos.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // Si salió bien se envía al inicio de sesión con el aviso de éxito.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // Si el token no era válido, el error se asocia al campo del correo
        // para que la pantalla lo muestre junto a él.
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
