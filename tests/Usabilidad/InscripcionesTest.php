<?php

namespace Tests\Usabilidad;

use App\Models\InscripcionEvento;



/**
 * Pantalla «Mis inscripciones» del estudiante: ver el detalle y descargar el
 * comprobante de cada inscripción.
 */
class InscripcionesTest extends UsabilidadTestCase
{
    /** El listado debe abrir para el estudiante. */
    public function test_el_estudiante_ve_su_listado_de_inscripciones(): void
    {
        $estudiante = $this->usuarioConRol('Estudiante');
        $props = $this->abrirPagina($estudiante, '/mis-inscripciones', 'Mis inscripciones');

        $this->assertArrayHasKey('inscripciones', $props, 'El listado no entrega inscripciones.');
        $this->assertArrayHasKey('resumen', $props, 'El listado no entrega el resumen.');
    }

    /**
     * Ver el detalle y descargar el comprobante de cada inscripción existente.
     *
     * Se recorren todas porque el fallo aparecía sólo en algunas: las que
     * apuntan a un proyecto que ya no existe.
     */
    public function test_el_detalle_y_el_comprobante_abren_para_toda_inscripcion(): void
    {
        // Sin esto, un error del servidor llega como página 500 y no se ve la causa.
        $this->withoutExceptionHandling();

        $fallos = [];

        foreach (InscripcionEvento::all() as $inscripcion) {
            $autor = $inscripcion->proyecto?->autor;

            // Sin autor no hay quien tenga permiso; se prueba con el administrador.
            $usuario = $autor ?? $this->usuarioConRol('Administrador');

            foreach (['', '/comprobante'] as $sufijo) {
                $respuesta = $this->actingAs($usuario)->get("/inscripciones/{$inscripcion->id}{$sufijo}");
                $codigo = $respuesta->getStatusCode();

                if ($codigo >= 500) {
                    $fallos[] = "inscripción {$inscripcion->id}{$sufijo}: código {$codigo}";
                }
            }
        }

        $this->assertSame(
            [],
            $fallos,
            "Inscripciones cuyo detalle o comprobante revienta:\n  - ".implode("\n  - ", $fallos)
        );
    }

    /**
     * El nombre del parámetro de la ruta debe coincidir con el del controlador.
     *
     * Ésta fue la causa del fallo: Route::resource singulariza en inglés y
     * generaba {inscripcione}, mientras el controlador recibe $inscripcion. Al
     * no coincidir, Laravel inyecta un modelo VACÍO en lugar del registro, y
     * $inscripcion->proyecto sale nulo («property autor_id on null»).
     *
     * La comprobación se hace sobre todas las rutas del proyecto porque el
     * mismo tropiezo acecha en cualquier recurso con nombre en español.
     */
    public function test_el_enlace_de_modelo_resuelve_en_todas_las_rutas(): void
    {
        $desajustes = [];

        foreach (app('router')->getRoutes() as $ruta) {
            $accion = $ruta->getActionName();

            if (! str_contains($accion, '@')) {
                continue;
            }

            [$clase, $metodo] = explode('@', $accion);

            if (! class_exists($clase) || ! method_exists($clase, $metodo)) {
                continue;
            }

            foreach ((new \ReflectionMethod($clase, $metodo))->getParameters() as $parametro) {
                $tipo = $parametro->getType();

                if (! $tipo || $tipo->isBuiltin() || ! str_starts_with($tipo->getName(), 'App\\Models\\')) {
                    continue;
                }

                if (! in_array($parametro->getName(), $ruta->parameterNames(), true)) {
                    $nombres = implode(', ', $ruta->parameterNames()) ?: 'ninguno';
                    $desajustes[] = "{$ruta->uri()} → {$metodo}(\${$parametro->getName()}), pero la ruta declara: {$nombres}";
                }
            }
        }

        $this->assertSame(
            [],
            $desajustes,
            "Rutas cuyo parámetro no coincide con el del controlador (Laravel inyectará un modelo vacío):\n  - "
            .implode("\n  - ", $desajustes)
        );
    }
}
