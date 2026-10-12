<?php

namespace Tests\Usabilidad;

use App\Models\Evaluacion;
use App\Models\EventoParticipante;
use App\Models\Rubrica;
use App\Support\CatalogosEvento;

/**
 * La pantalla «Mis evaluaciones» tenía un buscador y un filtro de estado que
 * no hacían nada: el botón «Aplicar filtros» recargaba la página sin cambiar
 * la consulta. Se reemplazó por un filtro de búsqueda y de rúbrica que sí
 * funcionan, y se añadió un resumen (total, promedio, mejor nota).
 */
class EvaluacionesIndexTest extends UsabilidadTestCase
{
    /**
     * Devuelve [Evento, User] para una combinación que todavía no tenga una
     * fila en evento_participantes (clave única evento_id+user_id).
     */
    private function eventoYEstudianteSinPropuesta(): array
    {
        $estudiantes = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))->get();

        foreach (\App\Models\Evento::all() as $evento) {
            $libre = $estudiantes->first(
                fn ($u) => ! EventoParticipante::where('evento_id', $evento->id)->where('user_id', $u->id)->exists()
            );

            if ($libre) {
                return [$evento, $libre];
            }
        }

        $this->fail('No se encontró una combinación de evento y estudiante libre para la prueba.');
    }

    /** El resumen y las rúbricas disponibles llegan a la pantalla. */
    public function test_la_pantalla_entrega_resumen_y_rubricas(): void
    {
        foreach (['Administrador', 'Coordinador', 'Jurado'] as $rol) {
            $props = $this->abrirPagina($this->usuarioConRol($rol), '/evaluaciones', "Evaluaciones ({$rol})");

            $this->assertArrayHasKey('resumen', $props, "Falta el resumen para {$rol}.");
            $this->assertArrayHasKey('total', $props['resumen']);
            $this->assertArrayHasKey('rubricas', $props, "Faltan las rúbricas del filtro para {$rol}.");
            $this->assertNotEmpty($props['rubricas']);
        }
    }

    /** El filtro por rúbrica de verdad acota los resultados. */
    public function test_el_filtro_por_rubrica_funciona(): void
    {
        $admin = $this->usuarioConRol('Administrador');
        $rubrica = Rubrica::whereHas('evaluaciones')->firstOrFail();
        $totalDeEsaRubrica = Evaluacion::where('rubrica_id', $rubrica->id)->count();

        $props = $this->abrirPagina(
            $admin,
            "/evaluaciones?rubrica_id={$rubrica->id}",
            'Evaluaciones filtradas por rúbrica'
        );

        $this->assertSame($totalDeEsaRubrica, $props['resumen']['total']);

        foreach ($props['evaluaciones']['data'] as $evaluacion) {
            $this->assertSame(
                $rubrica->id,
                $evaluacion['rubrica_id'],
                'Una evaluación de otra rúbrica se coló en el listado filtrado.'
            );
        }
    }

    /** El buscador de verdad busca por proyecto o por jurado. */
    public function test_el_buscador_funciona(): void
    {
        $admin = $this->usuarioConRol('Administrador');
        $evaluacion = Evaluacion::with('proyecto')->whereHas('proyecto')->firstOrFail();
        $palabra = substr($evaluacion->proyecto->titulo, 0, 8);

        $props = $this->abrirPagina($admin, '/evaluaciones?search='.urlencode($palabra), 'Evaluaciones con búsqueda');

        $this->assertGreaterThan(0, count($props['evaluaciones']['data']), 'La búsqueda no encontró la evaluación que debía.');
        $this->assertTrue(
            collect($props['evaluaciones']['data'])->contains('id', $evaluacion->id),
            'La evaluación esperada no aparece en los resultados de la búsqueda.'
        );
    }

    /** El resumen del jurado combina sus notas de rúbrica y de eventos. */
    public function test_el_resumen_del_jurado_combina_ambas_fuentes(): void
    {
        $jurado = $this->usuarioConRol('Jurado');

        $rubricaCount = Evaluacion::where('jurado_id', $jurado->id)->whereNotNull('proyecto_id')->count();
        $eventosCount = EventoParticipante::where('evaluador_id', $jurado->id)
            ->where('estado_evaluacion', 'evaluado')->count();

        $props = $this->abrirPagina($jurado, '/evaluaciones', 'Evaluaciones del jurado');

        $this->assertSame($rubricaCount + $eventosCount, $props['resumen']['total']);
        $this->assertNull($props['resumen']['jurados_distintos'], 'El jurado no debería ver el indicador de "jurados distintos".');
    }

    /**
     * Una propuesta de evento calificada con rúbrica no debe aparecer
     * también en la tabla de «proyectos de semillero calificados»: ya se
     * muestra en «Propuestas de evento calificadas». Antes de filtrar por
     * proyecto_id, se colaba aquí con el proyecto en blanco («Proyecto
     * eliminado»), porque esas filas no tienen proyecto_id.
     */
    public function test_una_evaluacion_de_evento_no_aparece_en_la_tabla_de_proyectos(): void
    {
        $jurado = $this->usuarioConRol('Jurado');
        $rubrica = Rubrica::with('criterios')->whereHas('criterios')->firstOrFail();

        // Un evento y un estudiante que todavía no tengan una propuesta entre
        // sí, porque evento_participantes tiene una clave única (evento_id, user_id).
        [$evento, $estudiante] = $this->eventoYEstudianteSinPropuesta();

        $propuesta = EventoParticipante::create([
            'evento_id' => $evento->id,
            'user_id' => $estudiante->id,
            'modalidad' => CatalogosEvento::MODALIDADES[0],
            'area_participacion' => CatalogosEvento::AREAS_PARTICIPACION[0],
            'titulo_proyecto' => 'Propuesta para probar el listado de evaluaciones',
            'descripcion_proyecto' => 'Prueba de usabilidad.',
            'estado' => 'aprobado',
            'evaluador_id' => $jurado->id,
            'estado_evaluacion' => 'sin_evaluar',
        ]);

        $puntajes = $rubrica->criterios->mapWithKeys(fn ($c) => [$c->id => $c->puntaje_maximo])->all();
        $this->actingAs($jurado)->post("/proyectos/{$propuesta->id}/evaluar", [
            'rubrica_id' => $rubrica->id,
            'puntajes' => $puntajes,
            'analisis_proyecto' => 'Texto.',
            'fortalezas' => 'Texto.',
            'debilidades' => 'Texto.',
            'recomendaciones' => 'Texto.',
        ])->assertSessionHasNoErrors();

        $props = $this->abrirPagina($jurado, '/evaluaciones', 'Evaluaciones (jurado, tras calificar un evento)');

        $this->assertFalse(
            collect($props['evaluaciones']['data'])->contains('evento_participante_id', $propuesta->id),
            'La evaluación de la propuesta de evento se coló en la tabla de proyectos de semillero.'
        );
    }
}
