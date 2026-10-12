<?php

namespace Tests\Usabilidad;

use App\Models\Evaluacion;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Proyecto;
use App\Models\Rubrica;
use App\Models\User;
use App\Support\CatalogosEvento;

/**
 * Rol Jurado — módulos: Evaluaciones y rúbricas.
 */
class JuradoTest extends UsabilidadTestCase
{
    private function jurado(): User
    {
        return $this->usuarioConRol('Jurado');
    }

    /**
     * Crea una propuesta de evento asignada al jurado y lista para evaluar.
     */
    private function propuestaAsignada(User $jurado): EventoParticipante
    {
        $evento = Evento::first();
        $this->assertNotNull($evento, 'No hay eventos en los datos de prueba.');

        $estudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->whereDoesntHave('participacionesEventos', fn ($q) => $q->where('evento_id', $evento->id))
            ->firstOrFail();

        return EventoParticipante::create([
            'evento_id' => $evento->id,
            'user_id' => $estudiante->id,
            'modalidad' => CatalogosEvento::MODALIDADES[0],
            'area_participacion' => CatalogosEvento::AREAS_PARTICIPACION[0],
            'titulo_proyecto' => 'Propuesta asignada al jurado',
            'descripcion_proyecto' => 'Propuesta usada en la prueba de usabilidad.',
            'estado' => 'aprobado',
            'evaluador_id' => $jurado->id,
            'estado_evaluacion' => 'sin_evaluar',
            'aula' => 'Aula 101',
            'horario' => '09:00',
            'programacion' => 'Ponencia',
        ]);
    }

    /** HU-22. Consultar los proyectos o propuestas asignados para evaluación. */
    public function test_hu22_consultar_proyectos_asignados(): void
    {
        $jurado = $this->jurado();
        $propuesta = $this->propuestaAsignada($jurado);

        // Pantalla «Evaluar Proyectos»: sólo lo que le toca evaluar.
        $props = $this->abrirPagina($jurado, '/mis-proyectos-evaluar', 'HU-22');
        $this->assertNotEmpty($props, 'HU-22: la pantalla de proyectos asignados no entrega datos.');

        $contenido = json_encode($props, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString(
            'Propuesta asignada al jurado',
            $contenido,
            'HU-22: la propuesta asignada no aparece en la lista del jurado.'
        );

        // Pantalla «Mis Evaluaciones»: el historial de notas ya puestas.
        $this->abrirPagina($jurado, '/evaluaciones', 'HU-22');

        // Proyectos asignados por rúbrica.
        $this->abrirPagina($jurado, '/evaluaciones/proyectos-asignados', 'HU-22');

        // El formulario de evaluación de la propuesta debe abrir.
        $this->abrirPagina($jurado, "/proyectos/{$propuesta->id}/evaluar", 'HU-22');
    }

    /** HU-23 y HU-24. Evaluar mediante rúbrica y registrar la evaluación en el sistema. */
    public function test_hu23_hu24_evaluar_con_rubrica_y_registrar(): void
    {
        $jurado = $this->jurado();

        $rubrica = Rubrica::with('criterios')->whereHas('criterios')->first();
        $this->assertNotNull($rubrica, 'HU-23: no hay rúbricas con criterios en los datos de prueba.');

        // Un proyecto que este jurado todavía no haya evaluado.
        $proyecto = Proyecto::whereDoesntHave('evaluaciones', fn ($q) => $q->where('jurado_id', $jurado->id))->first();
        $this->assertNotNull($proyecto, 'HU-23: no hay proyectos pendientes para este jurado.');

        // El jurado consulta la rúbrica antes de calificar.
        $this->abrirPagina($jurado, "/rubricas/{$rubrica->id}", 'HU-23');
        $this->abrirPagina($jurado, '/rubricas', 'HU-23');

        // Abre el formulario de calificación con el proyecto y la rúbrica.
        $this->abrirPagina(
            $jurado,
            "/evaluaciones/create?proyecto_id={$proyecto->id}&rubrica_id={$rubrica->id}",
            'HU-23'
        );

        // Sin indicar proyecto y rúbrica debe avisar, no romperse.
        $this->actingAs($jurado)->get('/evaluaciones/create')
            ->assertRedirect(route('evaluaciones.proyectos-asignados'));

        // Califica cada criterio con su puntaje máximo.
        $puntajes = $rubrica->criterios
            ->mapWithKeys(fn ($c) => [$c->id => $c->puntaje_maximo])
            ->all();

        $this->actingAs($jurado)->post('/evaluaciones', [
            'proyecto_id' => $proyecto->id,
            'rubrica_id' => $rubrica->id,
            'observaciones' => 'Evaluación registrada durante la prueba de usabilidad.',
            'puntajes' => $puntajes,
        ])->assertSessionHasNoErrors();

        // HU-24: la evaluación queda almacenada oficialmente.
        $evaluacion = Evaluacion::where('proyecto_id', $proyecto->id)
            ->where('jurado_id', $jurado->id)
            ->first();

        $this->assertNotNull($evaluacion, 'HU-24: la evaluación no quedó almacenada.');
        $this->assertSame('Evaluado', $evaluacion->estado, 'HU-24: la evaluación no quedó marcada como evaluada.');
        $this->assertCount(
            $rubrica->criterios->count(),
            $evaluacion->detalleEvaluaciones,
            'HU-24: no se guardó el puntaje de todos los criterios.'
        );

        // La nota debe quedar dentro de la escala institucional 0.0 – 100.0.
        $nota = (float) $evaluacion->nota_final;
        $this->assertGreaterThanOrEqual(0, $nota, 'HU-24: la nota quedó por debajo de 0.');
        $this->assertLessThanOrEqual(100, $nota, "HU-24: la nota {$nota} supera el máximo de 100.");

        // Con todos los criterios al máximo la nota debe ser 100.0.
        $this->assertEqualsWithDelta(100.0, $nota, 0.01, 'HU-24: puntaje perfecto no produjo una nota de 100.0.');

        // Y puede consultarla.
        $this->abrirPagina($jurado, "/evaluaciones/{$evaluacion->id}", 'HU-24');

        // No debe poder evaluar dos veces el mismo proyecto.
        $this->actingAs($jurado)->post('/evaluaciones', [
            'proyecto_id' => $proyecto->id,
            'rubrica_id' => $rubrica->id,
            'observaciones' => 'Intento duplicado.',
            'puntajes' => $puntajes,
        ])->assertSessionHasErrors('proyecto');
    }

    /**
     * HU-24 (vía evento). Registrar la evaluación de una propuesta asignada.
     *
     * Desde que las propuestas de evento también se califican con rúbrica
     * (antes era un único campo de nota de 0 a 5 sin criterios), la pantalla
     * «Evaluar proyecto» ofrece la rúbrica de ese evento+modalidad —o una
     * genérica, de respaldo— y el jurado puntúa cada criterio.
     */
    public function test_hu24_registrar_evaluacion_de_una_propuesta_de_evento(): void
    {
        $jurado = $this->jurado();
        $propuesta = $this->propuestaAsignada($jurado);

        $props = $this->abrirPagina($jurado, "/proyectos/{$propuesta->id}/evaluar", 'HU-24');
        $this->assertNotEmpty($props['rubricas'] ?? [], 'HU-24: no se ofreció ninguna rúbrica para calificar la propuesta.');

        $rubrica = $props['rubricas'][0];
        $puntajes = collect($rubrica['criterios'])->mapWithKeys(fn ($c) => [$c['id'] => $c['puntaje_maximo']])->all();

        $this->actingAs($jurado)->post("/proyectos/{$propuesta->id}/evaluar", [
            'rubrica_id' => $rubrica['id'],
            'puntajes' => $puntajes,
            'analisis_proyecto' => 'El proyecto está bien fundamentado.',
            'fortalezas' => 'Metodología clara.',
            'debilidades' => 'Faltan referencias recientes.',
            'recomendaciones' => 'Ampliar el marco teórico.',
        ])->assertSessionHasNoErrors();

        $propuesta->refresh();
        $this->assertSame('evaluado', $propuesta->estado_evaluacion, 'HU-24: la propuesta no quedó evaluada.');
        $this->assertNotNull($propuesta->fecha_evaluacion, 'HU-24: no se registró la fecha de evaluación.');

        // Con todos los criterios al máximo la nota debe ser 100.0, y debe
        // quedar sincronizada en evento_participantes (lo que leen reportes
        // y el resto de pantallas).
        $this->assertEqualsWithDelta(100.0, (float) $propuesta->nota_final, 0.01, 'HU-24: no se guardó la nota correctamente.');

        $evaluacion = Evaluacion::where('evento_participante_id', $propuesta->id)->where('jurado_id', $jurado->id)->first();
        $this->assertNotNull($evaluacion, 'HU-24: no quedó registrada la evaluación con rúbrica.');
        $this->assertCount(count($rubrica['criterios']), $evaluacion->detalleEvaluaciones, 'HU-24: no se guardó el puntaje de todos los criterios.');

        // Un puntaje fuera del rango del criterio (0, cuando el mínimo es 1) debe rechazarse.
        $otra = $this->propuestaAsignada($jurado);
        $puntajesInvalidos = array_map(fn () => 0, $puntajes);
        $this->actingAs($jurado)->post("/proyectos/{$otra->id}/evaluar", [
            'rubrica_id' => $rubrica['id'],
            'puntajes' => $puntajesInvalidos,
            'analisis_proyecto' => 'Texto.',
            'fortalezas' => 'Texto.',
            'debilidades' => 'Texto.',
            'recomendaciones' => 'Texto.',
        ])->assertSessionHasErrors();
    }

    /** Un jurado no debe poder evaluar una propuesta que no le fue asignada. */
    public function test_el_jurado_no_evalua_propuestas_ajenas(): void
    {
        $jurado = $this->jurado();

        $otroJurado = User::whereHas('roles', fn ($q) => $q->where('name', 'Jurado'))
            ->where('id', '!=', $jurado->id)
            ->first();

        if (! $otroJurado) {
            $this->markTestSkipped('Sólo hay un jurado en los datos de prueba.');
        }

        $ajena = $this->propuestaAsignada($otroJurado);

        $rubrica = Rubrica::with('criterios')->whereHas('criterios')->first();
        $puntajes = $rubrica->criterios->mapWithKeys(fn ($c) => [$c->id => $c->puntaje_maximo])->all();

        $this->actingAs($jurado)->post("/proyectos/{$ajena->id}/evaluar", [
            'rubrica_id' => $rubrica->id,
            'puntajes' => $puntajes,
            'analisis_proyecto' => 'Texto.',
            'fortalezas' => 'Texto.',
            'debilidades' => 'Texto.',
            'recomendaciones' => 'Texto.',
        ]);

        $this->assertNotSame(
            'evaluado',
            $ajena->fresh()->estado_evaluacion,
            'Seguridad: un jurado evaluó una propuesta que no tenía asignada.'
        );
    }
}
