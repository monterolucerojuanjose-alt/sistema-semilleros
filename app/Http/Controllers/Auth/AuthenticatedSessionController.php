<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DE INICIO Y CIERRE DE SESIÓN
 * ============================================================================
 *
 * Gestiona las tres operaciones del ciclo de vida de una sesión:
 * mostrar el formulario de acceso, validar las credenciales y cerrar la sesión.
 *
 * La comprobación de la contraseña no se hace aquí, sino en LoginRequest, que
 * además limita los intentos fallidos para frenar ataques de fuerza bruta.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión.
     *
     * @return Response La pantalla Auth/Login renderizada con Inertia.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            // Indica a la pantalla si debe mostrar el enlace «¿Olvidaste tu
            // contraseña?». Se consulta si la ruta existe en lugar de darlo por
            // hecho, para que el enlace no aparezca roto si se desactiva
            // la recuperación de contraseñas.
            'canResetPassword' => Route::has('password.request'),

            // Mensaje informativo que dejó la petición anterior, por ejemplo
            // «Se envió el enlace de recuperación a tu correo».
            'status' => session('status'),
        ]);
    }

    /**
     * Procesa el intento de inicio de sesión.
     *
     * @param  LoginRequest  $request  Petición ya validada: comprueba las
     *                                 credenciales y controla los intentos fallidos.
     * @return RedirectResponse Redirección al panel principal.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Comprueba usuario y contraseña. Si no coinciden, lanza una excepción
        // de validación y la ejecución no continúa más allá de esta línea.
        $request->authenticate();

        // Genera un identificador de sesión nuevo tras autenticarse. Esto evita
        // el ataque de «fijación de sesión», en el que un atacante induce a la
        // víctima a usar un identificador que él ya conoce; al regenerarlo
        // después del acceso, el identificador antiguo queda inservible.
        $request->session()->regenerate();

        // «intended» devuelve al usuario a la página que intentaba abrir antes
        // de que se le pidiera iniciar sesión. Si no había ninguna, va al panel.
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Cierra la sesión del usuario.
     *
     * @param  Request  $request  Petición entrante.
     * @return RedirectResponse Redirección a la página de bienvenida.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Desautentica al usuario del guardián «web», que es el que usa
        // sesiones con cookies (a diferencia de un guardián de API con token).
        Auth::guard('web')->logout();

        // Elimina todos los datos almacenados en la sesión.
        $request->session()->invalidate();

        // Renueva el token CSRF. Sin esto, el token anterior seguiría siendo
        // válido y podría reutilizarse desde el navegador ya cerrado.
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
