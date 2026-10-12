<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DE CONFIRMACIÓN DE CONTRASEÑA
 * ============================================================================
 *
 * Pide al usuario que vuelva a escribir su contraseña antes de ejecutar una
 * acción delicada, aunque ya tenga la sesión abierta.
 *
 * Sirve para acotar el daño de una sesión dejada abierta: tener el navegador
 * a mano no basta para las operaciones protegidas por esta confirmación, hay
 * que conocer además la contraseña.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Muestra la pantalla que solicita la contraseña.
     *
     * @return Response La pantalla Auth/ConfirmPassword renderizada con Inertia.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Comprueba la contraseña y marca la sesión como confirmada.
     *
     * @param  Request  $request  Petición con la contraseña escrita.
     * @return RedirectResponse Redirección a la acción que se quería realizar.
     *
     * @throws ValidationException Si la contraseña no es correcta.
     */
    public function store(Request $request): RedirectResponse
    {
        // «validate» comprueba las credenciales sin iniciar una sesión nueva,
        // que es justo lo que interesa aquí: el usuario ya está dentro.
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // Se anota el momento de la confirmación. El middleware que protege las
        // acciones delicadas lee esta marca y vuelve a pedir la contraseña
        // cuando ha pasado demasiado tiempo (tres horas por omisión).
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
