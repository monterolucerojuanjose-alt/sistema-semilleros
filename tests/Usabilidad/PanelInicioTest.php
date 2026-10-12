<?php

namespace Tests\Usabilidad;

/**
 * El panel de inicio es la primera pantalla tras iniciar sesión.
 *
 * Estas pruebas comprueban que ningún rol entre a una pantalla vacía: todos
 * deben recibir sus indicadores y las secciones de contenido.
 */
class PanelInicioTest extends UsabilidadTestCase
{
    public static function roles(): array
    {
        return [
            'Administrador' => ['Administrador', ['totalUsuarios', 'totalProyectos', 'totalSemilleros', 'totalCertificados']],
            'Coordinador' => ['Coordinador', ['totalSemilleros', 'totalProyectos', 'totalEvaluaciones', 'totalCertificados']],
            'Docente/Tutor' => ['Docente/Tutor', ['misSemilleros', 'estudiantesAsignados', 'misProyectos', 'solicitudesPendientes']],
            'Jurado' => ['Jurado', ['evaluacionesPendientes', 'proyectosEvaluados', 'calificacionPromedio', 'totalAsignados']],
            'Estudiante' => ['Estudiante', ['misProyectos', 'inscripcionesActivas', 'solicitudesPendientes', 'misCertificados']],
        ];
    }

    /**
     * @dataProvider roles
     */
    public function test_el_panel_entrega_contenido_a_cada_rol(string $rol, array $indicadores): void
    {
        $usuario = $this->usuarioConRol($rol);
        $props = $this->abrirPagina($usuario, '/dashboard', 'Panel de inicio');

        // Indicadores propios del rol.
        foreach ($indicadores as $clave) {
            $this->assertArrayHasKey(
                $clave,
                $props['stats'] ?? [],
                "El panel de {$rol} no entrega el indicador «{$clave}»."
            );
        }

        // Secciones de contenido: deben existir siempre, aunque vengan vacías,
        // porque la pantalla las dibuja a partir de ellas.
        foreach (['proximosEventos', 'actividadReciente', 'ultimasNotificaciones'] as $seccion) {
            $this->assertArrayHasKey($seccion, $props, "El panel de {$rol} no entrega «{$seccion}».");
            $this->assertIsArray($props[$seccion], "«{$seccion}» debería ser una lista en el panel de {$rol}.");
        }
    }

    /** El jurado debe ver en el panel lo que tiene pendiente de calificar. */
    public function test_el_jurado_ve_su_trabajo_pendiente(): void
    {
        $jurado = $this->usuarioConRol('Jurado');
        $props = $this->abrirPagina($jurado, '/dashboard', 'Panel del jurado');

        $this->assertIsArray($props['pendientesJurado'], 'El jurado no recibe su lista de proyectos por evaluar.');
    }

    /** Los demás roles no deben recibir la lista propia del jurado. */
    public function test_los_otros_roles_no_reciben_la_lista_del_jurado(): void
    {
        foreach (['Administrador', 'Coordinador', 'Docente/Tutor', 'Estudiante'] as $rol) {
            $props = $this->abrirPagina($this->usuarioConRol($rol), '/dashboard', 'Panel de inicio');

            $this->assertNull(
                $props['pendientesJurado'],
                "El rol {$rol} está recibiendo la lista de evaluaciones del jurado."
            );
        }
    }

    /** Las estadísticas del panel son sólo para quien administra o coordina. */
    public function test_las_estadisticas_estan_restringidas(): void
    {
        $endpoints = [
            '/api/dashboard/resumen-general',
            '/api/dashboard/proyectos-por-estado',
            '/api/dashboard/participacion-por-rol',
            '/api/dashboard/evaluaciones-por-rubrica',
            '/api/dashboard/estadisticas-mensuales',
        ];

        // Permitidas para el coordinador.
        foreach ($endpoints as $endpoint) {
            $codigo = $this->actingAs($this->usuarioConRol('Coordinador'))->getJson($endpoint)->getStatusCode();
            $this->assertSame(200, $codigo, "El coordinador no puede consultar {$endpoint} (código {$codigo}).");
        }

        // Denegadas para el estudiante.
        foreach ($endpoints as $endpoint) {
            $codigo = $this->actingAs($this->usuarioConRol('Estudiante'))->getJson($endpoint)->getStatusCode();
            $this->assertSame(403, $codigo, "Un estudiante alcanzó {$endpoint} (código {$codigo}).");
        }
    }
}
