<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ============================================================================
 * CONTROLADOR DEL PERFIL DE USUARIO
 * ============================================================================
 *
 * Gestiona los datos que cada usuario puede modificar de sí mismo: su nombre
 * y correo, su foto de perfil y, si lo desea, la eliminación de su cuenta.
 *
 * Todos los métodos operan sobre $request->user(), es decir, sobre quien tiene
 * la sesión abierta. Nunca reciben un identificador de usuario por parámetro,
 * y eso es intencionado: así es imposible que alguien modifique el perfil de
 * otra persona cambiando un número en la dirección.
 *
 * El cambio de contraseña no está aquí, sino en Auth\PasswordController.
 */
class ProfileController extends Controller
{
    /**
     * Muestra el formulario de perfil.
     *
     * @param  Request  $request  Petición entrante.
     * @return Response La pantalla Profile/Edit renderizada con Inertia.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            // Indica a la pantalla si debe mostrar el aviso de «verifica tu
            // correo». Depende de si el modelo User implementa esa interfaz.
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,

            // Mensaje de confirmación de la acción anterior.
            'status' => session('status'),
        ]);
    }

    /**
     * Actualiza el nombre y el correo del usuario.
     *
     * @param  ProfileUpdateRequest  $request  Petición con los datos ya validados.
     * @return RedirectResponse Vuelve a la pantalla de perfil.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // «fill» asigna solo los campos que pasaron la validación, nunca todo
        // lo que venga en el formulario. Sin esa restricción, alguien podría
        // añadir un campo oculto y modificar datos que no le corresponden.
        $request->user()->fill($request->validated());

        // «isDirty» detecta si el correo cambió respecto al guardado. Si es
        // así, la nueva dirección aún no está verificada: se borra la marca
        // para que el sistema vuelva a pedir la verificación. De lo contrario
        // bastaría con cambiar el correo para saltarse ese control.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Sube o reemplaza la foto de perfil.
     *
     * @param  Request  $request  Petición con el archivo de imagen.
     * @return RedirectResponse Vuelve al perfil con el aviso de confirmación.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            // Tres controles sobre el archivo, cada uno por un motivo distinto:
            //   image  → que sea realmente una imagen y no otro tipo de archivo
            //   max    → 2048 kilobytes (2 MB), para no llenar el disco
            //   mimes  → formatos concretos admitidos
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif,webp'],
        ]);

        $user = $request->user();

        // Se borra la foto anterior antes de guardar la nueva. Sin esto, cada
        // cambio dejaría un archivo huérfano ocupando espacio para siempre.
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Se guarda en el disco «public», que es el único accesible desde el
        // navegador a través del enlace public/storage.
        $path = $request->file('avatar')->store('avatars', 'public');

        // «forceFill» escribe el campo aunque no esté en la lista de campos
        // rellenables del modelo. Aquí es seguro porque la ruta la genera el
        // propio sistema, no el usuario.
        $user->forceFill(['avatar_path' => $path])->save();

        return Redirect::route('profile.edit')->with('status', 'Foto de perfil actualizada correctamente.');
    }

    /**
     * Elimina la foto de perfil y deja la imagen por defecto.
     *
     * @param  Request  $request  Petición entrante.
     * @return RedirectResponse Vuelve al perfil con el aviso de confirmación.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            // Se borran las dos cosas: el archivo del disco y la referencia en
            // la base de datos. Si solo se borrara la referencia, el archivo
            // quedaría ocupando espacio sin que nada apunte a él.
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return Redirect::route('profile.edit')->with('status', 'Foto de perfil eliminada.');
    }

    /**
     * Elimina definitivamente la cuenta del usuario.
     *
     * @param  Request  $request  Petición con la contraseña de confirmación.
     * @return RedirectResponse Redirección a la página de inicio.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Se exige la contraseña antes de borrar. Es una acción irreversible,
        // así que no basta con tener la sesión abierta.
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Se limpia la foto del disco antes de eliminar el registro, porque
        // después ya no habría forma de saber qué archivo le pertenecía.
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // El orden es importante: primero se cierra la sesión y luego se borra
        // el usuario. Al revés, la sesión quedaría apuntando a un usuario que
        // ya no existe y la siguiente petición fallaría.
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
