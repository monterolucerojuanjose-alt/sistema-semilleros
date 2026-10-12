<?php

namespace Tests\Usabilidad;

use App\Models\EventoParticipante;

/**
 * Dos comprobaciones que salieron al redactar el manual de usuario:
 * que ninguna pantalla referenciada falte, y que no queden textos en inglés.
 */
class PantallasYTextosTest extends UsabilidadTestCase
{
    /**
     * Todo componente que un controlador pide con Inertia::render debe existir.
     *
     * Si falta el archivo .vue la ruta responde 200 pero el navegador muestra
     * un error al intentar cargar el componente: un botón roto que no aparece
     * en ningún registro del servidor.
     */
    public function test_existen_todas_las_pantallas_que_piden_los_controladores(): void
    {
        $faltantes = [];

        $controladores = array_merge(
            glob(base_path('app/Http/Controllers/*.php')),
            glob(base_path('app/Http/Controllers/*/*.php'))
        );

        foreach ($controladores as $archivo) {
            preg_match_all(
                '/Inertia::render\(\s*[\'"]([^\'"]+)[\'"]/',
                file_get_contents($archivo),
                $coincidencias
            );

            foreach ($coincidencias[1] as $componente) {
                if (! file_exists(resource_path("js/Pages/{$componente}.vue"))) {
                    $faltantes[] = "{$componente}.vue (lo pide ".basename($archivo).')';
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($faltantes)),
            "Pantallas que un controlador pide pero no existen:\n  - "
            .implode("\n  - ", array_unique($faltantes))
        );
    }

    /** La ficha del historial de inscripciones debe abrir. */
    public function test_el_detalle_del_historial_abre(): void
    {
        $participacion = EventoParticipante::first();
        $this->assertNotNull($participacion, 'No hay participaciones en los datos de prueba.');

        foreach (['Administrador', 'Coordinador'] as $rol) {
            $props = $this->abrirPagina(
                $this->usuarioConRol($rol),
                "/historial-inscripciones/{$participacion->id}",
                'Historial de inscripciones'
            );

            $this->assertArrayHasKey('inscripcion', $props, "La ficha no entrega datos para el rol {$rol}.");
        }
    }

    /**
     * La ficha de un usuario debe abrir, y sus listas deben traer los registros.
     *
     * Al acotar columnas en un eager load hay que incluir la clave con la que
     * Eloquent empareja (tutor_id, autor_id); si falta, la relación llega vacía
     * aunque el contador diga que hay filas, y la sección no se dibuja.
     */
    public function test_la_ficha_de_usuario_abre_con_sus_listas(): void
    {
        $admin = $this->usuarioConRol('Administrador');

        foreach (['Estudiante', 'Docente/Tutor', 'Jurado', 'Coordinador'] as $rol) {
            $usuario = $this->usuarioConRol($rol);
            $props = $this->abrirPagina($admin, "/users/{$usuario->id}", 'Ficha de usuario');

            $this->assertArrayHasKey('user', $props, "La ficha de {$rol} no entrega el usuario.");
            $this->assertArrayHasKey('resumen', $props, "La ficha de {$rol} no entrega el resumen.");

            // Lo que anuncia el resumen debe estar también en la lista.
            $comprobaciones = [
                ['semillerosComoTutor', 'semilleros_como_tutor'],
                ['semilleros', 'semilleros_aprobados'],
                ['proyectos', 'proyectos'],
            ];

            foreach ($comprobaciones as [$claveResumen, $claveLista]) {
                $this->assertCount(
                    $props['resumen'][$claveResumen],
                    $props['user'][$claveLista] ?? [],
                    "En la ficha de {$rol}, «{$claveResumen}» dice {$props['resumen'][$claveResumen]} "
                    ."pero la lista «{$claveLista}» trae otra cantidad."
                );
            }
        }
    }

    /**
     * Las etiquetas del paginador deben estar traducidas.
     *
     * La aplicación está en español y su idioma de respaldo también, así que
     * si falta lang/es/pagination.php no hay de dónde sacar el texto y Laravel
     * devuelve la clave tal cual: en pantalla se leía «pagination.previous».
     */
    public function test_el_paginador_esta_traducido(): void
    {
        foreach (['pagination.previous', 'pagination.next'] as $clave) {
            $etiqueta = __($clave);

            $this->assertNotSame(
                $clave,
                $etiqueta,
                "Falta la traducción de «{$clave}»: crea lang/es/pagination.php."
            );

            $this->assertStringNotContainsStringIgnoringCase(
                'previous',
                $etiqueta,
                "La etiqueta del paginador sigue en inglés: «{$etiqueta}»."
            );
            $this->assertStringNotContainsStringIgnoringCase(
                'next',
                $etiqueta,
                "La etiqueta del paginador sigue en inglés: «{$etiqueta}»."
            );
        }
    }

    /**
     * Ninguna pantalla paginada debe mostrar una clave de traducción cruda.
     */
    public function test_ninguna_pantalla_paginada_muestra_claves_crudas(): void
    {
        $pantallas = [
            ['Administrador', '/semilleros'],
            ['Administrador', '/proyectos'],
            ['Administrador', '/users'],
            ['Administrador', '/certificados'],
            ['Administrador', '/historial-inscripciones'],
            ['Coordinador', '/resultados-evaluacion'],
            ['Estudiante', '/mis-certificados'],
        ];

        $problemas = [];

        foreach ($pantallas as [$rol, $url]) {
            $props = $this->abrirPagina($this->usuarioConRol($rol), $url, 'Paginación');
            $contenido = json_encode($props, JSON_UNESCAPED_UNICODE);

            if (str_contains($contenido, 'pagination.')) {
                $problemas[] = "{$url} muestra una clave sin traducir";
            }
        }

        $this->assertSame(
            [],
            $problemas,
            "Pantallas con claves de traducción a la vista:\n  - ".implode("\n  - ", $problemas)
        );
    }

    /** Las pantallas de acceso y recuperación deben estar en español. */
    public function test_las_pantallas_de_acceso_estan_en_espanol(): void
    {
        // Frases que Laravel Breeze deja en inglés si no se traduce la pantalla.
        $enIngles = [
            'Forgot your password', 'Email Password Reset Link', 'Reset Password',
            'Confirm Password', 'Email Verification', 'Resend Verification Email',
            'Log Out', 'Remember me', 'Already registered', 'Confirm your password',
        ];

        $problemas = [];

        foreach (glob(resource_path('js/Pages/Auth/*.vue')) as $archivo) {
            $contenido = file_get_contents($archivo);

            foreach ($enIngles as $frase) {
                if (str_contains($contenido, $frase)) {
                    $problemas[] = basename($archivo).": «{$frase}»";
                }
            }
        }

        $this->assertSame(
            [],
            $problemas,
            "Textos en inglés en las pantallas de acceso:\n  - ".implode("\n  - ", $problemas)
        );
    }

    /**
     * El correo de recuperación de contraseña debe verse bien y en español.
     *
     * Cubre dos fallos ya encontrados: la clave «Regards» sin la coma que usa
     * el layout de Laravel (@lang('Regards,')) nunca hacía match y el correo
     * mostraba «Regards,» en inglés; y la frase del pie del botón lleva un
     * salto de línea real incrustado en la clave de traducción, así que una
     * versión con un espacio en su lugar tampoco coincidía. Ambas viven en
     * lang/es.json, fuera del alcance de la prueba de arriba.
     */
    public function test_el_correo_de_recuperacion_se_ve_bien_y_en_espanol(): void
    {
        $usuario = $this->usuarioConRol('Estudiante');

        $notificacion = new \Illuminate\Auth\Notifications\ResetPassword('token-de-prueba');
        $mensaje = $notificacion->toMail($usuario);
        // MailMessage::render() reproduce exactamente el camino que sigue un
        // envío real (MailChannel llama a lo mismo internamente).
        $html = (string) $mensaje->render();

        $this->assertSame(
            'Recuperación de contraseña',
            $mensaje->subject,
            'El asunto del correo de recuperación no está traducido.'
        );

        foreach (['Regards', 'having trouble clicking', 'copy and paste', 'into your web browser'] as $rastro) {
            $this->assertStringNotContainsStringIgnoringCase(
                $rastro,
                $html,
                "El correo de recuperación sigue mostrando texto en inglés: «{$rastro}»."
            );
        }

        // La franja de marca y el botón deben llevar el color índigo del
        // sistema, no el gris genérico del tema por defecto de Laravel.
        $this->assertStringContainsString('#4f46e5', $html, 'El correo no lleva los colores de marca del sistema.');
    }

    /** Los mensajes de recuperación que devuelve el servidor deben estar traducidos. */
    public function test_los_mensajes_de_recuperacion_estan_traducidos(): void
    {
        $this->assertSame('es', config('app.locale'), 'El idioma del sistema no está en español.');

        foreach (['reset', 'sent', 'throttled', 'token', 'user'] as $clave) {
            $mensaje = __("passwords.{$clave}");

            $this->assertNotSame(
                "passwords.{$clave}",
                $mensaje,
                "Falta la traducción de «passwords.{$clave}» en lang/es/passwords.php."
            );

            // Un mensaje sin traducir sigue conteniendo las palabras en inglés.
            foreach (['password', 'email address', 'We have', 'Please wait'] as $rastro) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $rastro,
                    $mensaje,
                    "El mensaje «passwords.{$clave}» sigue en inglés: «{$mensaje}»."
                );
            }
        }
    }

    /** Pedir un enlace de recuperación debe responder en español. */
    public function test_solicitar_recuperacion_responde_en_espanol(): void
    {
        $this->get('/forgot-password')->assertStatus(200);

        // Un correo que no existe: el aviso de error debe estar en español.
        $respuesta = $this->post('/forgot-password', ['email' => 'nadie@umariana.edu.co']);
        $respuesta->assertSessionHasErrors('email');

        $mensaje = session('errors')->first('email');

        $this->assertStringNotContainsStringIgnoringCase(
            'We can',
            $mensaje,
            "El aviso sigue en inglés: «{$mensaje}»."
        );
    }
}
