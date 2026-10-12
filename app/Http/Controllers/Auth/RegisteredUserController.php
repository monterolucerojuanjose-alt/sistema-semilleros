<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * ============================================================================
 * CONTROLADOR DE REGISTRO DE USUARIOS
 * ============================================================================
 *
 * Permite que una persona cree su propia cuenta desde la pantalla pública
 * de registro. Toda cuenta creada por esta vía nace con el rol «Estudiante»,
 * que es el de menores privilegios del sistema.
 *
 * Los roles superiores (Administrador, Docente, Jurado) no se pueden obtener
 * registrándose: los asigna un administrador desde la gestión de usuarios.
 * Esta separación es deliberada, porque de lo contrario cualquier visitante
 * podría concederse permisos de administración.
 */
class RegisteredUserController extends Controller
{
    /**
     * Muestra el formulario de registro.
     *
     * @return Response La pantalla Auth/Register renderizada con Inertia.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Crea la cuenta y deja al usuario dentro del sistema.
     *
     * @param  Request  $request  Datos del formulario de registro.
     * @return RedirectResponse Redirección al panel principal.
     *
     * @throws \Illuminate\Validation\ValidationException Si los datos no son válidos.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',

            // «lowercase» obliga a guardar el correo en minúsculas y «unique»
            // impide dos cuentas con el mismo correo. Van juntas a propósito:
            // sin la conversión a minúsculas, «Juan@x.com» y «juan@x.com»
            // pasarían como correos distintos y se duplicaría la persona.
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,

            // «confirmed» exige que coincida con el campo password_confirmation.
            // Password::defaults() aplica la política de contraseñas definida
            // de forma central, para no repetir aquí la longitud mínima.
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,

            // La contraseña nunca se guarda tal cual: Hash::make aplica bcrypt,
            // un cifrado de un solo sentido. Ni siquiera un administrador con
            // acceso a la base de datos puede leer la contraseña original.
            'password' => Hash::make($request->password),

            'estado' => 'activo',
        ]);

        // findOrCreate en lugar de findByName: en la base de pruebas los roles
        // pueden no estar sembrados todavía, y findByName reventaría. Así el
        // registro funciona igual en producción que durante las pruebas.
        Role::findOrCreate('Estudiante');
        $user->assignRole('Estudiante');

        // Dispara el evento de registro, que es lo que provoca el envío del
        // correo de verificación.
        event(new Registered($user));

        // Inicia la sesión automáticamente, para que el usuario no tenga que
        // escribir de nuevo las credenciales que acaba de elegir.
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
