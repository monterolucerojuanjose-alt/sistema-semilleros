<?php

namespace Tests\Usabilidad;

/**
 * Comprueba que cada opción del menú lateral lleva a una pantalla que carga.
 *
 * Un enlace del menú que responda 404 o 403 es un botón que no funciona para
 * quien lo tiene a la vista, aunque el resto del sistema esté bien.
 */
class NavegacionTest extends UsabilidadTestCase
{
    /**
     * Debe reflejar exactamente el menú de resources/js/Components/Sidebar.vue.
     */
    public static function menuPorRol(): array
    {
        return [
            'Administrador' => ['Administrador', [
                '/dashboard', '/users', '/semilleros', '/proyectos', '/eventos', '/rubricas',
                '/historial-inscripciones', '/propuestas/pendientes', '/resultados-evaluacion', '/certificados',
                '/reportes', '/notificaciones', '/auditoria',
            ]],
            'Coordinador' => ['Coordinador', [
                '/dashboard', '/semilleros', '/proyectos', '/eventos', '/rubricas',
                '/historial-inscripciones', '/propuestas/pendientes', '/resultados-evaluacion', '/reportes', '/certificados',
                '/notificaciones', '/auditoria',
            ]],
            'Docente/Tutor' => ['Docente/Tutor', [
                '/dashboard', '/semilleros', '/mis-semilleros', '/proyectos', '/mis-proyectos-evaluar',
                '/resultados-evaluacion', '/eventos', '/estudiantes', '/solicitudes', '/notificaciones',
            ]],
            'Estudiante' => ['Estudiante', [
                '/dashboard', '/semilleros', '/proyectos', '/mis-inscripciones', '/mis-notas',
                '/mis-certificados', '/eventos', '/notificaciones',
            ]],
            'Jurado' => ['Jurado', [
                '/dashboard', '/mis-proyectos-evaluar', '/evaluaciones', '/rubricas', '/notificaciones',
            ]],
        ];
    }

    /**
     * @dataProvider menuPorRol
     */
    public function test_todas_las_opciones_del_menu_abren(string $rol, array $enlaces): void
    {
        $usuario = $this->usuarioConRol($rol);
        $rotos = [];

        foreach ($enlaces as $enlace) {
            $codigo = $this->actingAs($usuario)->get($enlace)->getStatusCode();

            if ($codigo !== 200) {
                $rotos[] = "{$enlace} respondió {$codigo}";
            }
        }

        $this->assertSame(
            [],
            $rotos,
            "Opciones del menú de {$rol} que no abren:\n  - ".implode("\n  - ", $rotos)
        );
    }

    /** Las pantallas de creación deben abrir para quien tiene el botón. */
    public function test_los_formularios_de_creacion_abren(): void
    {
        $casos = [
            ['Coordinador', '/semilleros/create'],
            ['Coordinador', '/eventos/create'],
            ['Coordinador', '/rubricas/create'],
            ['Administrador', '/users/create'],
            ['Administrador', '/rubricas/create'],
            ['Estudiante', '/nuevo-proyecto'],
        ];

        $rotos = [];

        foreach ($casos as [$rol, $enlace]) {
            $codigo = $this->actingAs($this->usuarioConRol($rol))->get($enlace)->getStatusCode();

            if ($codigo !== 200) {
                $rotos[] = "{$rol} → {$enlace} respondió {$codigo}";
            }
        }

        $this->assertSame([], $rotos, "Formularios que no abren:\n  - ".implode("\n  - ", $rotos));
    }

    /** El perfil debe abrir para todos los roles. */
    public function test_el_perfil_abre_para_todos_los_roles(): void
    {
        foreach (['Administrador', 'Coordinador', 'Docente/Tutor', 'Jurado', 'Estudiante'] as $rol) {
            $codigo = $this->actingAs($this->usuarioConRol($rol))->get('/profile')->getStatusCode();

            $this->assertSame(200, $codigo, "El perfil respondió {$codigo} para el rol {$rol}.");
        }
    }
}
