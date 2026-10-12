<?php

namespace Tests\Usabilidad;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Proyecto;
use App\Models\Semillero;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Rol Estudiante — módulos: Autenticación, Gestión de semilleros, Proyectos,
 * Eventos, Evaluaciones y Certificados.
 */
class EstudianteTest extends UsabilidadTestCase
{
    private function estudiante(): User
    {
        return $this->usuarioConRol('Estudiante');
    }

    /** HU-25. Registrarse e iniciar sesión para acceder a las funcionalidades. */
    public function test_hu25_registrarse_e_iniciar_sesion(): void
    {
        // El formulario de registro debe estar disponible.
        $this->get('/register')->assertStatus(200);

        $this->post('/register', [
            'name' => 'Estudiante Nuevo Prueba',
            'email' => 'estudiante.nuevo@umariana.edu.co',
            'password' => 'Clave.Prueba123',
            'password_confirmation' => 'Clave.Prueba123',
        ])->assertSessionHasNoErrors();

        $nuevo = User::where('email', 'estudiante.nuevo@umariana.edu.co')->first();
        $this->assertNotNull($nuevo, 'HU-25: la cuenta no quedó creada.');
        $this->assertTrue($nuevo->hasRole('Estudiante'), 'HU-25: la cuenta nueva no recibió el rol Estudiante.');
        $this->assertAuthenticatedAs($nuevo);

        // Cierra sesión: el registro sólo está disponible para visitantes.
        $this->post('/logout');
        $this->assertGuest();

        // No debe permitirse repetir un correo ya registrado.
        $this->post('/register', [
            'name' => 'Otro',
            'email' => 'estudiante.nuevo@umariana.edu.co',
            'password' => 'Clave.Prueba123',
            'password_confirmation' => 'Clave.Prueba123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::where('email', 'estudiante.nuevo@umariana.edu.co')->count(),
            'HU-25: se creó una segunda cuenta con el mismo correo.'
        );

        // Vuelve a entrar con sus credenciales.

        $this->post('/login', [
            'email' => 'estudiante.nuevo@umariana.edu.co',
            'password' => 'Clave.Prueba123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($nuevo->fresh());
        $this->abrirPagina($nuevo, '/dashboard', 'HU-25');
    }

    /** HU-27. Solicitar ingreso a un semillero. */
    public function test_hu27_solicitar_ingreso_a_un_semillero(): void
    {
        $estudiante = $this->estudiante();
        $tutor = $this->usuarioConRol('Docente/Tutor');

        // Debe poder consultar el catálogo de semilleros.
        $props = $this->abrirPagina($estudiante, '/semilleros', 'HU-27');
        $this->assertArrayHasKey('semilleros', $props, 'HU-27: el catálogo no entrega semilleros.');

        $datosBase = [
            'descripcion' => 'Semillero para la prueba de usabilidad.',
            'linea_tematica' => 'Pruebas',
            'tutor_id' => $tutor->id,
        ];

        // Semillero abierto: la solicitud entra y queda pendiente de aprobación.
        $abierto = Semillero::create($datosBase + ['nombre' => 'Abierto (prueba)', 'estado' => 'activo']);

        $this->actingAs($estudiante)
            ->post("/semilleros/{$abierto->id}/solicitud")
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            $abierto->usuarios()->wherePivot('user_id', $estudiante->id)
                ->wherePivot('estado_solicitud', 'pendiente')->exists(),
            'HU-27: la solicitud no quedó registrada.'
        );

        // No debe poder solicitar dos veces el mismo semillero.
        $this->actingAs($estudiante)->post("/semilleros/{$abierto->id}/solicitud");
        $this->assertSame(
            1,
            $abierto->usuarios()->wherePivot('user_id', $estudiante->id)->count(),
            'HU-27: se registró una solicitud duplicada.'
        );

        // Semillero inactivo: no debe admitir solicitudes.
        $inactivo = Semillero::create($datosBase + ['nombre' => 'Inactivo (prueba)', 'estado' => 'inactivo']);
        $this->actingAs($estudiante)->post("/semilleros/{$inactivo->id}/solicitud");
        $this->assertFalse(
            $inactivo->usuarios()->wherePivot('user_id', $estudiante->id)->exists(),
            'HU-27: se admitió una solicitud a un semillero inactivo.'
        );

        // Semillero con clave: se rechaza la clave equivocada y se acepta la correcta.
        $conClave = Semillero::create($datosBase + [
            'nombre' => 'Con clave (prueba)',
            'estado' => 'activo',
            'clave_acceso' => bcrypt('clave1234'),
        ]);

        $this->actingAs($estudiante)
            ->post("/semilleros/{$conClave->id}/solicitud", ['clave_acceso' => 'equivocada'])
            ->assertSessionHasErrors('clave_acceso');

        $this->assertFalse(
            $conClave->usuarios()->wherePivot('user_id', $estudiante->id)->exists(),
            'HU-27: se admitió el ingreso con una clave incorrecta.'
        );

        $this->actingAs($estudiante)
            ->post("/semilleros/{$conClave->id}/solicitud", ['clave_acceso' => 'clave1234'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            $conClave->usuarios()->wherePivot('user_id', $estudiante->id)->exists(),
            'HU-27: la clave correcta no permitió solicitar el ingreso.'
        );
    }

    /** HU-29. Crear un proyecto para postular una propuesta de investigación. */
    public function test_hu29_crear_un_proyecto(): void
    {
        $estudiante = $this->estudiante();
        $tutor = $this->usuarioConRol('Docente/Tutor');

        $semillero = Semillero::create([
            'nombre' => 'Semillero para proyectos (prueba)',
            'descripcion' => 'Semillero de la prueba de usabilidad.',
            'linea_tematica' => 'Pruebas',
            'tutor_id' => $tutor->id,
            'estado' => 'activo',
        ]);

        // Sin ser miembro aprobado no debe poder crear el proyecto.
        $this->actingAs($estudiante)->post('/proyectos', [
            'titulo' => 'Proyecto sin pertenecer al semillero',
            'resumen' => str_repeat('Resumen de la investigación propuesta. ', 3),
            'semillero_id' => $semillero->id,
        ]);

        $this->assertFalse(
            Proyecto::where('titulo', 'Proyecto sin pertenecer al semillero')->exists(),
            'HU-29: se creó un proyecto en un semillero al que el estudiante no pertenece.'
        );

        // Se aprueba su ingreso al semillero.
        $semillero->usuarios()->attach($estudiante->id, [
            'rol_en_semillero' => 'estudiante',
            'estado_solicitud' => 'aprobado',
            'fecha' => now(),
        ]);

        $this->abrirPagina($estudiante, '/nuevo-proyecto', 'HU-29');

        // Un resumen demasiado corto debe rechazarse con un mensaje en español.
        $corto = $this->actingAs($estudiante)->post('/proyectos', [
            'titulo' => 'Proyecto de prueba',
            'resumen' => 'Muy corto',
            'semillero_id' => $semillero->id,
        ]);
        $corto->assertSessionHasErrors('resumen');

        $mensaje = session('errors')->first('resumen');
        $this->assertStringNotContainsString(
            'validation.',
            $mensaje,
            "HU-29: el mensaje de validación no está traducido: «{$mensaje}»."
        );

        // Ahora sí, con datos válidos.
        $this->actingAs($estudiante)->post('/proyectos', [
            'titulo' => 'Proyecto Prueba Usabilidad',
            'resumen' => str_repeat('Resumen de la investigación propuesta. ', 3),
            'semillero_id' => $semillero->id,
        ])->assertSessionHasNoErrors();

        $proyecto = Proyecto::where('titulo', 'Proyecto Prueba Usabilidad')->first();
        $this->assertNotNull($proyecto, 'HU-29: el proyecto no quedó creado.');
        $this->assertSame('Inscrito', $proyecto->estado, 'HU-29: el proyecto no quedó en estado Inscrito.');
        $this->assertNotEmpty($proyecto->codigo, 'HU-29: el proyecto no recibió código.');
        $this->assertSame($estudiante->id, $proyecto->autor_id, 'HU-29: el proyecto no quedó a nombre del estudiante.');

        // Y puede consultarlo.
        $this->abrirPagina($estudiante, '/proyectos', 'HU-29');
        $this->abrirPagina($estudiante, "/proyectos/{$proyecto->id}", 'HU-29');
    }

    /** HU-32. Inscribirse en un evento con una propuesta académica. */
    public function test_hu32_inscribirse_en_un_evento(): void
    {
        Storage::fake('public');

        $estudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->whereDoesntHave('participacionesEventos')
            ->first() ?? $this->estudiante();

        $evento = Evento::create([
            'titulo' => 'Evento Prueba Inscripción',
            'descripcion' => 'Evento de la prueba de usabilidad.',
            'precio' => 0,
            'fecha_inicio' => now()->addWeek(),
            'fecha_fin' => now()->addWeeks(2),
            'ubicacion' => 'Auditorio',
            'cupo_maximo' => 50,
            'estado' => 'activo',
            'creado_por' => $this->usuarioConRol('Coordinador')->id,
        ]);

        $this->abrirPagina($estudiante, '/eventos', 'HU-32');
        $this->abrirPagina($estudiante, "/eventos/{$evento->id}", 'HU-32');

        $datos = [
            'titulo_proyecto' => 'Propuesta para el evento',
            'descripcion_proyecto' => 'Descripción de la propuesta presentada al evento.',
            'archivo_proyecto' => UploadedFile::fake()->create('propuesta.pdf', 500, 'application/pdf'),
            'modalidad' => \App\Support\CatalogosEvento::MODALIDADES[0],
            'area_participacion' => \App\Support\CatalogosEvento::AREAS_PARTICIPACION[0],
            'participantes' => [
                ['nombre' => $estudiante->name, 'email' => $estudiante->email],
            ],
        ];

        $this->actingAs($estudiante)
            ->post("/eventos/{$evento->id}/inscribir", $datos)
            ->assertSessionHasNoErrors();

        $inscripcion = EventoParticipante::where('evento_id', $evento->id)
            ->where('user_id', $estudiante->id)
            ->first();

        $this->assertNotNull($inscripcion, 'HU-32: la inscripción no quedó registrada.');
        $this->assertSame('pendiente', $inscripcion->estado, 'HU-32: la inscripción no quedó pendiente de revisión.');
        $this->assertSame($datos['modalidad'], $inscripcion->modalidad, 'HU-32: no se guardó la modalidad de participación.');
        $this->assertSame($datos['area_participacion'], $inscripcion->area_participacion, 'HU-32: no se guardó el área de participación.');
        $this->assertNotNull($inscripcion->archivo_proyecto, 'HU-32: no se guardó el archivo de la propuesta.');

        // Sin archivo la inscripción debe rechazarse.
        $otroEstudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->where('id', '!=', $estudiante->id)
            ->firstOrFail();

        $sinArchivo = $datos;
        unset($sinArchivo['archivo_proyecto']);

        $this->actingAs($otroEstudiante)
            ->post("/eventos/{$evento->id}/inscribir", $sinArchivo)
            ->assertSessionHasErrors('archivo_proyecto');

        // El estudiante consulta sus inscripciones.
        $this->abrirPagina($estudiante, '/mis-inscripciones', 'HU-32');
    }

    /** HU-35. Consultar sus notas o resultados de evaluación. */
    public function test_hu35_consultar_sus_notas(): void
    {
        $estudiante = $this->estudiante();
        $jurado = $this->usuarioConRol('Jurado');

        $evento = Evento::first();
        $this->assertNotNull($evento, 'HU-35: no hay eventos en los datos de prueba.');

        // Se le registra una nota de evento para que la pantalla tenga contenido.
        EventoParticipante::updateOrCreate(
            ['evento_id' => $evento->id, 'user_id' => $estudiante->id],
            [
                'modalidad' => \App\Support\CatalogosEvento::MODALIDADES[0],
                'area_participacion' => \App\Support\CatalogosEvento::AREAS_PARTICIPACION[0],
                'titulo_proyecto' => 'Propuesta calificada',
                'descripcion_proyecto' => 'Propuesta de la prueba de usabilidad.',
                'estado' => 'aprobado',
                'evaluador_id' => $jurado->id,
                'estado_evaluacion' => 'evaluado',
                'nota_final' => 84.0,
                'analisis_proyecto' => 'Buen trabajo, bien sustentado.',
                'fecha_evaluacion' => now(),
            ]
        );

        $props = $this->abrirPagina($estudiante, '/mis-notas', 'HU-35');

        $this->assertArrayHasKey('evaluadas', $props, 'HU-35: la pantalla no entrega las notas.');
        $this->assertArrayHasKey('resumen', $props, 'HU-35: la pantalla no entrega el resumen académico.');
        $this->assertNotEmpty($props['evaluadas'], 'HU-35: el estudiante no ve ninguna nota.');

        // Toda nota debe estar dentro de la escala institucional 0.0 – 100.0.
        foreach ($props['evaluadas'] as $nota) {
            $this->assertGreaterThanOrEqual(0, $nota['nota'], 'HU-35: hay una nota menor que 0.');
            $this->assertLessThanOrEqual(100, $nota['nota'], "HU-35: la nota {$nota['nota']} supera el máximo de 100.0.");
        }

        $promedio = $props['resumen']['promedio'];
        $this->assertNotNull($promedio, 'HU-35: no se calculó el promedio.');
        $this->assertLessThanOrEqual(100, $promedio, 'HU-35: el promedio supera el máximo de 100.0.');

        // Y puede abrir el detalle de una evaluación de evento.
        $conDetalle = collect($props['evaluadas'])->firstWhere('detalle_id', '!=', null);
        if ($conDetalle) {
            $this->abrirPagina($estudiante, "/mis-notas/{$conDetalle['detalle_id']}", 'HU-35');
        }
    }

    /** HU-36. Consultar y descargar sus certificados. */
    public function test_hu36_consultar_y_descargar_certificados(): void
    {
        $estudiante = $this->estudiante();

        $props = $this->abrirPagina($estudiante, '/mis-certificados', 'HU-36');
        $this->assertNotEmpty($props, 'HU-36: la pantalla de certificados no entrega datos.');

        // Si tiene certificados, debe poder abrir y descargar el primero.
        $certificado = \App\Models\Certificate::where('user_id', $estudiante->id)->first();

        if (! $certificado) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->abrirPagina($estudiante, "/certificados/{$certificado->id}", 'HU-36');

        $descarga = $this->actingAs($estudiante)->get("/certificados/{$certificado->id}/descargar");
        $this->assertSame(200, $descarga->getStatusCode(), 'HU-36: la descarga del certificado falla.');
    }

    /** Un estudiante no debe alcanzar pantallas de otros roles. */
    public function test_el_estudiante_no_accede_a_funciones_de_otros_roles(): void
    {
        $estudiante = $this->estudiante();

        $prohibidas = ['/users', '/auditoria', '/reportes', '/rubricas', '/semilleros/create', '/eventos/create'];

        foreach ($prohibidas as $url) {
            $respuesta = $this->actingAs($estudiante)->get($url);

            $this->assertContains(
                $respuesta->getStatusCode(),
                [403, 302],
                "Seguridad: un estudiante alcanzó {$url} con código {$respuesta->getStatusCode()}."
            );
        }
    }
}
