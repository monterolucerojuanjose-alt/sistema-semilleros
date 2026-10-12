<?php

namespace Tests\Usabilidad;

use App\Models\Semillero;
use App\Models\User;

/**
 * Los avisos de confirmación deben llegar a la pantalla.
 *
 * HandleInertiaRequests sólo compartía la clave 'exito', pero los controladores
 * guardan el aviso como 'success'. El resultado era que la acción se ejecutaba
 * y la pantalla no decía nada: el estudiante enviaba su solicitud de ingreso y
 * creía que no había funcionado.
 */
class MensajesFlashTest extends UsabilidadTestCase
{
    /** La clave que usan los controladores debe ser la que se comparte. */
    public function test_el_aviso_de_exito_llega_a_la_pantalla(): void
    {
        $estudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->whereDoesntHave('semilleros')
            ->first() ?? $this->usuarioConRol('Estudiante');

        $semillero = Semillero::where('estado', 'activo')
            ->whereDoesntHave('usuarios', fn ($q) => $q->where('users.id', $estudiante->id))
            ->firstOrFail();

        $this->actingAs($estudiante)
            ->post("/semilleros/{$semillero->id}/solicitud")
            ->assertSessionHasNoErrors();

        // El aviso viaja en la sesión hasta la siguiente pantalla.
        $props = $this->abrirPagina($estudiante, '/semilleros', 'Aviso de éxito');

        $this->assertArrayHasKey('flash', $props, 'La pantalla no recibe la sección de avisos.');
        $this->assertNotEmpty(
            $props['flash']['success'] ?? null,
            'El aviso de éxito no llega a la pantalla: revisa que HandleInertiaRequests comparta la clave «success».'
        );
    }

    /**
     * Toda clave de aviso que usen los controladores debe estar compartida.
     *
     * Si alguien añade un ->with('aviso', ...) que el middleware no expone, el
     * mensaje se pierde en silencio igual que pasó con 'success'.
     */
    public function test_todas_las_claves_de_aviso_estan_compartidas(): void
    {
        $compartidas = array_keys(
            (new \App\Http\Middleware\HandleInertiaRequests)->share(request())['flash']
        );

        $usadas = [];

        foreach (glob(base_path('app/Http/Controllers/*.php')) as $archivo) {
            preg_match_all(
                '/->with\(\s*[\'"]([a-zA-Z_]+)[\'"]\s*,/',
                file_get_contents($archivo),
                $coincidencias
            );

            foreach ($coincidencias[1] as $clave) {
                $usadas[$clave][] = basename($archivo);
            }
        }

        $perdidas = [];

        foreach ($usadas as $clave => $donde) {
            // 'with' también se usa para cargar relaciones; sólo interesan los
            // avisos que el middleware debería exponer.
            if (! in_array($clave, ['success', 'error', 'exito', 'info', 'warning', 'aviso'], true)) {
                continue;
            }

            if (! in_array($clave, $compartidas, true)) {
                $perdidas[] = "«{$clave}» se usa en ".implode(', ', array_unique($donde)).' pero el middleware no la comparte';
            }
        }

        $this->assertSame(
            [],
            $perdidas,
            "Avisos que nunca llegan a la pantalla:\n  - ".implode("\n  - ", $perdidas)
        );
    }
}
