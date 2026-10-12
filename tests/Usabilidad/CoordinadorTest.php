<?php

namespace Tests\Usabilidad;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Proyecto;
use App\Models\Semillero;
use App\Models\User;

/**
 * Rol Coordinador — módulos: Gestión de semilleros, proyectos, eventos
 * y evaluaciones.
 */
class CoordinadorTest extends UsabilidadTestCase
{
    private function coordinador(): User
    {
        return $this->usuarioConRol('Coordinador');
    }

    /** HU-10. Crear, editar y administrar un semillero de investigación. */
    public function test_hu10_administrar_semillero(): void
    {
        $coordinador = $this->coordinador();
        $tutor = $this->usuarioConRol('Docente/Tutor');

        // Consultar el listado y el formulario de creación.
        $props = $this->abrirPagina($coordinador, '/semilleros', 'HU-10');
        $this->assertArrayHasKey('semilleros', $props, 'HU-10: el listado no entrega semilleros.');
        $this->abrirPagina($coordinador, '/semilleros/create', 'HU-10');

        // Crear, incluyendo cupo y clave de acceso.
        $this->actingAs($coordinador)->post('/semilleros', [
            'nombre' => 'Semillero Prueba Usabilidad',
            'descripcion' => 'Semillero creado durante la prueba de usabilidad.',
            'linea_tematica' => 'Pruebas de software',
            'tutor_id' => $tutor->id,
            'cupo_maximo' => 10,
            'clave_acceso' => 'clave1234',
        ])->assertSessionHasNoErrors();

        $semillero = Semillero::where('nombre', 'Semillero Prueba Usabilidad')->first();
        $this->assertNotNull($semillero, 'HU-10: el semillero no quedó creado.');
        $this->assertSame(10, $semillero->cupo_maximo, 'HU-10: no se guardó el cupo máximo.');
        $this->assertTrue($semillero->requiere_clave, 'HU-10: no se guardó la clave de acceso.');
        $this->assertNotSame('clave1234', $semillero->getAttributes()['clave_acceso'], 'HU-10: la clave quedó guardada sin cifrar.');

        // Consultar la ficha y el formulario de edición.
        $this->abrirPagina($coordinador, "/semilleros/{$semillero->id}", 'HU-10');
        $this->abrirPagina($coordinador, "/semilleros/{$semillero->id}/edit", 'HU-10');

        // Editar: cambiar el nombre y desactivarlo, conservando la clave.
        $this->actingAs($coordinador)->put("/semilleros/{$semillero->id}", [
            'nombre' => 'Semillero Prueba Editado',
            'descripcion' => $semillero->descripcion,
            'linea_tematica' => $semillero->linea_tematica,
            'tutor_id' => $tutor->id,
            'estado' => 'inactivo',
            'cupo_maximo' => 15,
            'clave_acceso' => '',
        ])->assertSessionHasNoErrors();

        $semillero->refresh();
        $this->assertSame('Semillero Prueba Editado', $semillero->nombre, 'HU-10: la edición no se guardó.');
        $this->assertSame('inactivo', $semillero->estado, 'HU-10: no se pudo desactivar el semillero.');
        $this->assertTrue($semillero->requiere_clave, 'HU-10: la edición borró la clave existente.');

        // Eliminar un semillero sin miembros ni proyectos.
        $this->actingAs($coordinador)->delete("/semilleros/{$semillero->id}")->assertSessionHasNoErrors();
        $this->assertNull(Semillero::find($semillero->id), 'HU-10: el semillero no se eliminó.');
    }

    /** HU-12. Cambiar el estado de un proyecto para controlar su avance. */
    public function test_hu12_cambiar_estado_de_proyecto(): void
    {
        $coordinador = $this->coordinador();

        $proyecto = Proyecto::first();
        $this->assertNotNull($proyecto, 'HU-12: no hay proyectos en los datos de prueba.');

        $props = $this->abrirPagina($coordinador, '/proyectos', 'HU-12');
        $this->assertArrayHasKey('proyectos', $props, 'HU-12: el listado no entrega proyectos.');
        $this->abrirPagina($coordinador, "/proyectos/{$proyecto->id}", 'HU-12');

        foreach (['En revisión', 'Aprobado', 'Rechazado'] as $estado) {
            $this->actingAs($coordinador)
                ->put("/proyectos/{$proyecto->id}/estado", ['estado' => $estado])
                ->assertSessionHasNoErrors();

            $this->assertSame($estado, $proyecto->fresh()->estado, "HU-12: no se aplicó el estado «{$estado}».");
        }

        // Un estado fuera del catálogo debe rechazarse.
        $this->actingAs($coordinador)
            ->put("/proyectos/{$proyecto->id}/estado", ['estado' => 'Inventado'])
            ->assertSessionHasErrors('estado');
    }

    /** HU-13. Crear, editar o eliminar un evento académico. */
    public function test_hu13_administrar_evento(): void
    {
        $coordinador = $this->coordinador();

        $props = $this->abrirPagina($coordinador, '/eventos', 'HU-13');
        $this->assertArrayHasKey('eventos', $props, 'HU-13: el listado no entrega eventos.');
        $this->abrirPagina($coordinador, '/eventos/create', 'HU-13');

        // Crear.
        $this->actingAs($coordinador)->post('/eventos', [
            'titulo' => 'Evento Prueba Usabilidad',
            'descripcion' => 'Evento creado durante la prueba de usabilidad.',
            'precio' => 0,
            'fecha_inicio' => now()->addWeek()->format('Y-m-d'),
            'fecha_fin' => now()->addWeeks(2)->format('Y-m-d'),
            'ubicacion' => 'Auditorio principal',
            'cupo_maximo' => 50,
            'estado' => 'activo',
        ])->assertSessionHasNoErrors();

        $evento = Evento::where('titulo', 'Evento Prueba Usabilidad')->first();
        $this->assertNotNull($evento, 'HU-13: el evento no quedó creado.');

        $this->abrirPagina($coordinador, "/eventos/{$evento->id}", 'HU-13');
        $this->abrirPagina($coordinador, "/eventos/{$evento->id}/edit", 'HU-13');

        // Editar.
        $this->actingAs($coordinador)->put("/eventos/{$evento->id}", [
            'titulo' => 'Evento Prueba Editado',
            'descripcion' => $evento->descripcion,
            'precio' => 0,
            'fecha_inicio' => $evento->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $evento->fecha_fin?->format('Y-m-d'),
            'ubicacion' => 'Sala B',
            'cupo_maximo' => 60,
            'estado' => 'activo',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Evento Prueba Editado', $evento->fresh()->titulo, 'HU-13: la edición no se guardó.');

        // La fecha de fin anterior al inicio debe rechazarse.
        $this->actingAs($coordinador)->put("/eventos/{$evento->id}", [
            'titulo' => 'Evento Prueba Editado',
            'descripcion' => $evento->descripcion,
            'precio' => 0,
            'fecha_inicio' => now()->addWeeks(3)->format('Y-m-d'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d'),
            'estado' => 'activo',
        ])->assertSessionHasErrors('fecha_fin');

        // Eliminar.
        $this->actingAs($coordinador)->delete("/eventos/{$evento->id}")->assertSessionHasNoErrors();
        $this->assertNull(Evento::find($evento->id), 'HU-13: el evento no se eliminó.');
    }

    /** HU-16. Asignar un evaluador o jurado a una propuesta. */
    public function test_hu16_asignar_evaluador_a_una_propuesta(): void
    {
        $coordinador = $this->coordinador();
        $jurado = $this->usuarioConRol('Jurado');
        $estudiante = $this->usuarioConRol('Estudiante');

        $evento = Evento::first();
        $this->assertNotNull($evento, 'HU-16: no hay eventos en los datos de prueba.');

        // Propuesta recién presentada, aún sin revisar.
        $propuesta = EventoParticipante::create([
            'evento_id' => $evento->id,
            'user_id' => $estudiante->id,
            'carrera' => 'Ingeniería de Sistemas',
            'titulo_proyecto' => 'Propuesta Prueba Usabilidad',
            'descripcion_proyecto' => 'Propuesta presentada durante la prueba de usabilidad.',
            'estado' => 'pendiente',
        ]);

        // La bandeja de propuestas pendientes debe mostrarla.
        $this->abrirPagina($coordinador, '/propuestas/pendientes', 'HU-16');
        $this->abrirPagina($coordinador, "/propuestas/{$propuesta->id}", 'HU-16');

        // Aprobar la propuesta asignándole jurado, aula y horario.
        $this->actingAs($coordinador)->post("/propuestas/{$propuesta->id}/aprobar", [
            'programacion' => 'Ponencia de 20 minutos',
            'aula' => 'Aula 301',
            'horario' => '10:00 - 10:20',
            'evaluador_id' => $jurado->id,
        ])->assertSessionHasNoErrors();

        $propuesta->refresh();
        $this->assertSame('aprobado', $propuesta->estado, 'HU-16: la propuesta no quedó aprobada.');
        $this->assertSame($jurado->id, $propuesta->evaluador_id, 'HU-16: no se asignó el evaluador.');
        $this->assertSame('sin_evaluar', $propuesta->estado_evaluacion, 'HU-16: no quedó marcada como pendiente de evaluar.');

        // El estudiante debe poder ver quién es el jurado asignado desde «Mis inscripciones».
        $props = $this->abrirPagina($estudiante, '/mis-inscripciones', 'HU-16 (vista del estudiante)');
        $aprobada = collect($props['propuestasEventosAprobadas'])->firstWhere('id', $propuesta->id);
        $this->assertNotNull($aprobada, 'HU-16: la propuesta aprobada no aparece en «Mis inscripciones» del estudiante.');
        $this->assertSame(
            $jurado->id,
            $aprobada['evaluador']['id'] ?? null,
            'HU-16: el estudiante no ve quién es el jurado asignado a su propuesta.'
        );

        // Aprobar sin evaluador debe rechazarse. Se usa otro estudiante porque
        // cada persona sólo puede tener una propuesta por evento.
        $otroEstudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->where('id', '!=', $estudiante->id)
            ->whereDoesntHave('participacionesEventos', fn ($q) => $q->where('evento_id', $evento->id))
            ->firstOrFail();

        $otra = EventoParticipante::create([
            'evento_id' => $evento->id,
            'user_id' => $otroEstudiante->id,
            'carrera' => 'Ingeniería de Sistemas',
            'titulo_proyecto' => 'Segunda propuesta de prueba',
            'descripcion_proyecto' => 'Sin evaluador.',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordinador)->post("/propuestas/{$otra->id}/aprobar", [
            'programacion' => 'Ponencia',
            'aula' => 'Aula 302',
            'horario' => '11:00',
        ])->assertSessionHasErrors('evaluador_id');
    }

    /** HU-17. Consultar los resultados de evaluación de los proyectos. */
    public function test_hu17_consultar_resultados_de_evaluacion(): void
    {
        $coordinador = $this->coordinador();

        $props = $this->abrirPagina($coordinador, '/resultados-evaluacion', 'HU-17');
        $this->assertNotEmpty($props, 'HU-17: la pantalla de resultados no entrega datos.');

        $this->abrirPagina($coordinador, '/evaluaciones', 'HU-17');
        $this->abrirPagina($coordinador, '/evaluaciones/ranking', 'HU-17');

        $respuesta = $this->actingAs($coordinador)->getJson('/api/estadisticas-evaluacion');
        $this->assertSame(200, $respuesta->getStatusCode(), 'HU-17: las estadísticas de evaluación fallan.');
    }
}
