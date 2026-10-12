<?php

namespace Tests\Usabilidad;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Rol Administrador — módulos: Autenticación, Gestión de usuarios y roles,
 * Dashboard y Auditoría.
 */
class AdministradorTest extends UsabilidadTestCase
{
    private function admin(): User
    {
        return $this->usuarioConRol('Administrador');
    }

    /** HU-01. Iniciar sesión para acceder de forma segura a las funciones administrativas. */
    public function test_hu01_iniciar_sesion(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['password' => Hash::make('Clave.Prueba123')])->save();

        // La pantalla de acceso debe estar disponible para quien no ha entrado.
        $this->get('/login')->assertStatus(200);

        // Credenciales incorrectas: no debe autenticar ni romper la aplicación.
        $fallido = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'clave-equivocada',
        ]);
        $fallido->assertSessionHasErrors('email');
        $this->assertGuest();

        // Credenciales correctas: entra y llega al panel.
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'Clave.Prueba123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
        $this->get('/dashboard')->assertStatus(200);
    }

    /** HU-02. Gestionar usuarios: crear, editar, consultar o eliminar una cuenta. */
    public function test_hu02_gestionar_usuarios(): void
    {
        $admin = $this->admin();

        // Consultar el listado.
        $props = $this->abrirPagina($admin, '/users', 'HU-02');
        $this->assertArrayHasKey('users', $props, 'HU-02: el listado no entrega usuarios.');

        // Crear.
        $this->actingAs($admin)->post('/users', [
            'name' => 'Usuario Prueba Usabilidad',
            'email' => 'prueba.usabilidad@umariana.edu.co',
            'password' => 'Clave.Prueba123',
            'password_confirmation' => 'Clave.Prueba123',
            'role' => 'Estudiante',
            'estado' => 'activo',
        ])->assertSessionHasNoErrors();

        $creado = User::where('email', 'prueba.usabilidad@umariana.edu.co')->first();
        $this->assertNotNull($creado, 'HU-02: el usuario no quedó creado.');
        $this->assertTrue($creado->hasRole('Estudiante'), 'HU-02: el usuario se creó sin su rol.');

        // Consultar la ficha de edición.
        $this->actingAs($admin)->get("/users/{$creado->id}/edit")->assertStatus(200);

        // Editar.
        $this->actingAs($admin)->put("/users/{$creado->id}", [
            'name' => 'Usuario Prueba Editado',
            'email' => 'prueba.usabilidad@umariana.edu.co',
            'role' => 'Estudiante',
            'estado' => 'activo',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Usuario Prueba Editado', $creado->fresh()->name, 'HU-02: la edición no se guardó.');

        // Eliminar.
        $this->actingAs($admin)->delete("/users/{$creado->id}")->assertSessionHasNoErrors();
        $this->assertNull(User::find($creado->id), 'HU-02: el usuario no se eliminó.');
    }

    /** HU-03. Asignar y retirar roles a un usuario para controlar sus permisos. */
    public function test_hu03_asignar_y_retirar_roles(): void
    {
        $admin = $this->admin();

        $usuario = User::create([
            'name' => 'Prueba Roles',
            'email' => 'prueba.roles@umariana.edu.co',
            'password' => Hash::make('Clave.Prueba123'),
        ]);

        $this->assertTrue(Role::where('name', 'Jurado')->exists(), 'HU-03: no existe el rol Jurado.');

        // Asignar el rol.
        $this->actingAs($admin)
            ->post("/users/{$usuario->id}/assign-role", ['role' => 'Jurado'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($usuario->fresh()->hasRole('Jurado'), 'HU-03: el rol no quedó asignado.');

        // Retirarlo.
        $this->actingAs($admin)
            ->delete("/users/{$usuario->id}/remove-role", ['role' => 'Jurado'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($usuario->fresh()->hasRole('Jurado'), 'HU-03: el rol no se retiró.');

        // Cambiar el rol desde la edición del usuario reemplaza el anterior.
        $this->actingAs($admin)->put("/users/{$usuario->id}", [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'role' => 'Coordinador',
            'estado' => 'activo',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($usuario->fresh()->hasRole('Coordinador'), 'HU-03: no se pudo cambiar el rol desde la edición.');
    }

    /** HU-06. Consultar el dashboard con las estadísticas generales del sistema. */
    public function test_hu06_dashboard_con_estadisticas(): void
    {
        $admin = $this->admin();

        $props = $this->abrirPagina($admin, '/dashboard', 'HU-06');
        $this->assertNotEmpty($props, 'HU-06: el dashboard no entrega ningún dato.');

        // Los paneles del dashboard se alimentan de estos servicios; si alguno
        // falla, el administrador ve tarjetas vacías sin saber por qué.
        $endpoints = [
            '/api/dashboard/resumen-general',
            '/api/dashboard/proyectos-por-estado',
            '/api/dashboard/proyectos-por-semillero',
            '/api/dashboard/participacion-por-rol',
            '/api/dashboard/estadisticas-mensuales',
        ];

        foreach ($endpoints as $endpoint) {
            $respuesta = $this->actingAs($admin)->getJson($endpoint);
            $this->assertSame(
                200,
                $respuesta->getStatusCode(),
                "HU-06: la estadística {$endpoint} respondió {$respuesta->getStatusCode()}."
            );
        }
    }

    /** HU-07. Revisar la auditoría del sistema para seguir las acciones de los usuarios. */
    public function test_hu07_revisar_auditoria(): void
    {
        $admin = $this->admin();

        $props = $this->abrirPagina($admin, '/auditoria', 'HU-07');
        $this->assertArrayHasKey('auditorias', $props, 'HU-07: la auditoría no entrega registros.');

        // Filtrar por acción, que es el uso habitual de la pantalla.
        $this->actingAs($admin)->get('/auditoria?accion=login')->assertStatus(200);

        // Las exportaciones deben descargar y no lanzar error.
        $this->actingAs($admin)->get('/auditoria/exportar-pdf')->assertStatus(200);
        $this->actingAs($admin)->get('/auditoria/exportar-excel')->assertStatus(200);
    }

    /** Un usuario sin rol de administrador no debe alcanzar las pantallas administrativas. */
    public function test_las_pantallas_administrativas_estan_protegidas(): void
    {
        $estudiante = $this->usuarioConRol('Estudiante');

        foreach (['/users', '/auditoria'] as $url) {
            $respuesta = $this->actingAs($estudiante)->get($url);

            $this->assertContains(
                $respuesta->getStatusCode(),
                [403, 302],
                "Seguridad: un estudiante alcanzó {$url} con código {$respuesta->getStatusCode()}."
            );
        }
    }
}
