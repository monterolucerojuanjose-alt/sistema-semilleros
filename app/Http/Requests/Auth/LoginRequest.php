<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * PETICIÓN DE INICIO DE SESIÓN
 * ============================================================================
 *
 * Valida los datos del formulario de acceso y comprueba las credenciales.
 *
 * Su parte más importante es el límite de intentos: tras cinco fallos, la
 * combinación de correo y dirección IP queda bloqueada durante un minuto.
 * Sin ese freno, un programa automático podría probar miles de contraseñas
 * por minuto hasta acertar (ataque de fuerza bruta).
 */
class LoginRequest extends FormRequest
{
    /**
     * Indica si el usuario tiene permiso para enviar esta petición.
     *
     * Devuelve siempre verdadero porque iniciar sesión es una acción pública:
     * cualquiera puede intentarlo, precisamente porque todavía no se sabe
     * quién es. El control real lo hace authenticate().
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación que se aplican a la petición.
     *
     * Solo comprueban el formato, no si las credenciales son correctas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Comprueba las credenciales e inicia la sesión.
     *
     * @return void
     *
     * @throws ValidationException Si las credenciales no son correctas o si
     *                             se superó el límite de intentos.
     */
    public function authenticate(): void
    {
        // Antes de mirar la contraseña se comprueba si esta combinación de
        // correo e IP está bloqueada. El orden importa: comprobarlo después
        // permitiría seguir probando contraseñas durante el bloqueo.
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Cada fallo suma uno al contador de intentos.
            RateLimiter::hit($this->throttleKey());

            // El mensaje es genérico («las credenciales no coinciden») a
            // propósito: decir «ese correo no existe» le confirmaría a un
            // atacante qué correos están registrados en el sistema.
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Acceso correcto: se borra el contador para que los fallos anteriores
        // no penalicen los próximos inicios de sesión legítimos.
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Comprueba que no se haya superado el límite de intentos.
     *
     * @return void
     *
     * @throws ValidationException Si ya hubo cinco intentos fallidos.
     */
    public function ensureIsNotRateLimited(): void
    {
        // Menos de cinco intentos: se puede seguir, no se hace nada.
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        // Evento de bloqueo. Es el punto donde se puede enganchar un aviso al
        // administrador o un registro de seguridad.
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Se informa del tiempo restante en segundos y en minutos, para que el
        // mensaje pueda redactarse en la unidad que resulte más clara.
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Construye la clave con la que se cuentan los intentos fallidos.
     *
     * Combina el correo y la dirección IP. Se usan las dos juntas a propósito:
     * solo con el correo, un atacante podría bloquear la cuenta de otra
     * persona a base de fallos; solo con la IP, se bloquearía a toda una
     * facultad que comparte salida a internet.
     *
     * «transliterate» convierte los caracteres acentuados a su equivalente
     * simple, para que la clave sea siempre válida en el almacén de caché.
     *
     * @return string Clave única para el contador de intentos.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
