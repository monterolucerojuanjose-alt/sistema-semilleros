<?php

namespace Tests\Usabilidad;

use App\Models\CriterioRubrica;
use App\Models\Evaluacion;
use App\Models\Evento;
use App\Models\Rubrica;
use App\Support\CatalogosEvento;

/**
 * Una rúbrica que ya calificó proyectos no puede perder sus criterios: eso
 * invalidaría las notas ya calculadas con ellos. Pero antes ese bloqueo era
 * total —ni el nombre se podía corregir— y el enlace «Editar» directamente
 * desaparecía del listado, dejando la rúbrica congelada sin explicación ni
 * forma de ajustarla para el siguiente semestre.
 */
class RubricasEdicionTest extends UsabilidadTestCase
{
    private function rubricaConEvaluacion(): Rubrica
    {
        $rubrica = Rubrica::create(['nombre' => 'Rúbrica de prueba', 'descripcion' => 'Original']);

        $criterio = CriterioRubrica::create([
            'rubrica_id' => $rubrica->id,
            'criterio' => 'Claridad',
            'peso' => 100,
            'puntaje_maximo' => 5,
        ]);

        // Un jurado no puede evaluar el mismo proyecto dos veces (índice único
        // proyecto_id+jurado_id), así que se busca una combinación libre en
        // vez de tomar siempre el primer proyecto y el primer jurado.
        $jurado = $this->usuarioConRol('Jurado');
        $proyecto = \App\Models\Proyecto::whereDoesntHave(
            'evaluaciones',
            fn ($q) => $q->where('jurado_id', $jurado->id)
        )->firstOrFail();

        Evaluacion::create([
            'proyecto_id' => $proyecto->id,
            'jurado_id' => $jurado->id,
            'rubrica_id' => $rubrica->id,
            'estado' => 'Evaluado',
            'nota_final' => 90.0,
        ]);

        return $rubrica->fresh();
    }

    /** El nombre y la descripción se pueden corregir aunque ya haya calificado proyectos. */
    public function test_el_nombre_se_puede_corregir_aunque_tenga_evaluaciones(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $rubrica = $this->rubricaConEvaluacion();

        $this->actingAs($coordinador)->put("/rubricas/{$rubrica->id}", [
            'nombre' => 'Nombre corregido',
            'descripcion' => 'Descripción corregida.',
            // Se manda un criterio distinto a propósito: debe ignorarse.
            'criterios' => [['criterio' => 'Otro criterio', 'peso' => 100, 'puntaje_maximo' => 10]],
        ])->assertSessionHasNoErrors();

        $rubrica->refresh();
        $this->assertSame('Nombre corregido', $rubrica->nombre);
        $this->assertSame('Descripción corregida.', $rubrica->descripcion);
    }

    /** Los criterios de una rúbrica con evaluaciones no cambian, aunque se envíen distintos. */
    public function test_los_criterios_no_cambian_si_la_rubrica_tiene_evaluaciones(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $rubrica = $this->rubricaConEvaluacion();
        $criterioOriginalId = $rubrica->criterios()->first()->id;

        $this->actingAs($coordinador)->put("/rubricas/{$rubrica->id}", [
            'nombre' => $rubrica->nombre,
            'descripcion' => $rubrica->descripcion,
            'criterios' => [
                ['criterio' => 'Un criterio nuevo', 'peso' => 50, 'puntaje_maximo' => 5],
                ['criterio' => 'Otro criterio nuevo', 'peso' => 50, 'puntaje_maximo' => 5],
            ],
        ]);

        $rubrica->refresh();
        $this->assertCount(1, $rubrica->criterios, 'El número de criterios cambió.');
        $this->assertSame(
            $criterioOriginalId,
            $rubrica->criterios()->first()->id,
            'El criterio original fue reemplazado por otro con distinto id.'
        );
        $this->assertSame('Claridad', $rubrica->criterios()->first()->criterio);
    }

    /** Duplicar crea una copia editable con los mismos criterios. */
    public function test_duplicar_crea_una_copia_con_los_mismos_criterios_y_editable(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $rubrica = $this->rubricaConEvaluacion();

        $respuesta = $this->actingAs($coordinador)->post("/rubricas/{$rubrica->id}/duplicar")
            ->assertSessionHasNoErrors();

        $copia = Rubrica::where('nombre', $rubrica->nombre.' (copia)')->firstOrFail();
        $this->assertCount(1, $copia->criterios);
        $this->assertSame('Claridad', $copia->criterios->first()->criterio);
        $this->assertSame(0, $copia->evaluaciones()->count(), 'La copia no debería traer evaluaciones.');

        // La redirección lleva directo a editarla.
        $respuesta->assertRedirect(route('rubricas.edit', $copia));

        // Y sus criterios sí se pueden cambiar libremente, al no tener evaluaciones.
        $this->actingAs($coordinador)->put("/rubricas/{$copia->id}", [
            'nombre' => $copia->nombre,
            'descripcion' => $copia->descripcion,
            'criterios' => [
                ['criterio' => 'Rigor metodológico', 'peso' => 70, 'puntaje_maximo' => 10],
                ['criterio' => 'Presentación', 'peso' => 30, 'puntaje_maximo' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $copia->fresh()->criterios);
    }

    /** El coordinador siempre ve un botón para editar, incluso con evaluaciones. */
    public function test_el_listado_siempre_ofrece_editar(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $rubrica = $this->rubricaConEvaluacion();

        $props = $this->abrirPagina($coordinador, '/rubricas', 'Listado de rúbricas');
        $enPantalla = collect($props['rubricas'])->firstWhere('id', $rubrica->id);

        $this->assertNotNull($enPantalla);
        $this->assertGreaterThan(0, $enPantalla['evaluaciones_count']);

        // La pantalla de edición debe abrir sin error (antes: variable "page" indefinida).
        $this->abrirPagina($coordinador, "/rubricas/{$rubrica->id}/edit", 'Editar rúbrica');
    }

    /**
     * total_pesos y completa deben llegar a la pantalla ya calculados.
     *
     * Los accesores existían en el modelo pero, sin declararlos en $appends,
     * nunca se serializaban hacia el JSON: el listado mostraba el porcentaje
     * vacío y todas las rúbricas como "Incompleta", aunque sus pesos sí
     * sumaran 100.
     */
    public function test_total_pesos_y_completa_llegan_a_la_pantalla(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $props = $this->abrirPagina($coordinador, '/rubricas', 'Listado de rúbricas');

        $this->assertNotEmpty($props['rubricas']);

        foreach ($props['rubricas'] as $rubrica) {
            $this->assertArrayHasKey('total_pesos', $rubrica, "Falta total_pesos en «{$rubrica['nombre']}».");
            $this->assertArrayHasKey('completa', $rubrica, "Falta completa en «{$rubrica['nombre']}».");
            $this->assertEqualsWithDelta(
                100.0,
                (float) $rubrica['total_pesos'],
                0.01,
                "«{$rubrica['nombre']}» no suma 100% de peso."
            );
            $this->assertTrue($rubrica['completa'], "«{$rubrica['nombre']}» debería marcarse como completa.");
        }
    }

    /**
     * Una rúbrica se puede ligar a un evento y una modalidad, para que el
     * jurado la encuentre al calificar las propuestas de ese evento.
     */
    public function test_una_rubrica_se_puede_crear_y_editar_con_evento_y_modalidad(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $evento = Evento::first();
        $this->assertNotNull($evento, 'No hay eventos en los datos de prueba.');

        $this->actingAs($coordinador)->post('/rubricas', [
            'nombre' => 'Rúbrica ligada a un evento',
            'descripcion' => 'Creada en la prueba de usabilidad.',
            'evento_id' => $evento->id,
            'modalidad' => CatalogosEvento::MODALIDADES[0],
            'criterios' => [
                ['criterio' => 'Claridad', 'peso' => 100, 'puntaje_maximo' => 5],
            ],
        ])->assertSessionHasNoErrors();

        $rubrica = Rubrica::where('nombre', 'Rúbrica ligada a un evento')->firstOrFail();
        $this->assertSame($evento->id, $rubrica->evento_id, 'No se guardó el evento de la rúbrica.');
        $this->assertSame(CatalogosEvento::MODALIDADES[0], $rubrica->modalidad, 'No se guardó la modalidad de la rúbrica.');

        // El evento y la modalidad se pueden corregir aunque ya tenga evaluaciones,
        // igual que el nombre y la descripción: no afectan ninguna nota ya puesta.
        $rubricaConEvaluacion = $this->rubricaConEvaluacion();
        $this->actingAs($coordinador)->put("/rubricas/{$rubricaConEvaluacion->id}", [
            'nombre' => $rubricaConEvaluacion->nombre,
            'descripcion' => $rubricaConEvaluacion->descripcion,
            'evento_id' => $evento->id,
            'modalidad' => CatalogosEvento::MODALIDADES[1],
        ])->assertSessionHasNoErrors();

        $this->assertSame($evento->id, $rubricaConEvaluacion->fresh()->evento_id);
        $this->assertSame(CatalogosEvento::MODALIDADES[1], $rubricaConEvaluacion->fresh()->modalidad);
    }

    /**
     * La descripción es obligatoria: es donde el jurado lee cómo se debe
     * calificar con esa rúbrica, así que no puede quedar vacía.
     */
    public function test_la_descripcion_es_obligatoria_al_crear_y_al_editar(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');

        $this->actingAs($coordinador)->post('/rubricas', [
            'nombre' => 'Rúbrica sin descripción',
            'criterios' => [
                ['criterio' => 'Claridad', 'peso' => 100, 'puntaje_maximo' => 5],
            ],
        ])->assertSessionHasErrors('descripcion');

        $this->assertFalse(Rubrica::where('nombre', 'Rúbrica sin descripción')->exists());

        $rubricaConEvaluacion = $this->rubricaConEvaluacion();
        $this->actingAs($coordinador)->put("/rubricas/{$rubricaConEvaluacion->id}", [
            'nombre' => $rubricaConEvaluacion->nombre,
            'descripcion' => '',
        ])->assertSessionHasErrors('descripcion');
    }

    /**
     * Un jurado sólo debe ver sus propias evaluaciones al abrir una rúbrica,
     * no las que pusieron otros jurados sobre el mismo proyecto o propuesta.
     */
    public function test_el_jurado_solo_ve_sus_propias_evaluaciones_en_la_rubrica(): void
    {
        $jurado = $this->usuarioConRol('Jurado');
        $otroJurado = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Jurado'))
            ->where('id', '!=', $jurado->id)
            ->first();

        if (! $otroJurado) {
            $this->markTestSkipped('Sólo hay un jurado en los datos de prueba.');
        }

        $rubrica = Rubrica::with('criterios')->whereHas('criterios')->firstOrFail();
        $puntajes = $rubrica->criterios->mapWithKeys(fn ($c) => [$c->id => $c->puntaje_maximo])->all();

        $proyectoJurado = \App\Models\Proyecto::whereDoesntHave('evaluaciones', fn ($q) => $q->where('jurado_id', $jurado->id))->firstOrFail();
        $proyectoOtro = \App\Models\Proyecto::whereDoesntHave('evaluaciones', fn ($q) => $q->where('jurado_id', $otroJurado->id))
            ->where('id', '!=', $proyectoJurado->id)
            ->firstOrFail();

        $this->actingAs($jurado)->post('/evaluaciones', [
            'proyecto_id' => $proyectoJurado->id,
            'rubrica_id' => $rubrica->id,
            'puntajes' => $puntajes,
        ])->assertSessionHasNoErrors();

        $this->actingAs($otroJurado)->post('/evaluaciones', [
            'proyecto_id' => $proyectoOtro->id,
            'rubrica_id' => $rubrica->id,
            'puntajes' => $puntajes,
        ])->assertSessionHasNoErrors();

        $props = $this->abrirPagina($jurado, "/rubricas/{$rubrica->id}", 'Rúbrica (vista del jurado)');
        $juradosEnLaLista = collect($props['rubrica']['evaluaciones'])->pluck('jurado_id')->unique();

        $this->assertTrue($juradosEnLaLista->contains($jurado->id), 'El jurado no ve ni siquiera su propia evaluación.');
        $this->assertFalse($juradosEnLaLista->contains($otroJurado->id), 'El jurado ve evaluaciones de otro jurado en la rúbrica.');
    }

    /**
     * Un puntaje de un criterio que no pertenece a la rúbrica elegida debe
     * rechazarse: si el jurado cambió de rúbrica en el formulario y quedó
     * algún puntaje de la anterior, no debe colarse en la evaluación.
     */
    public function test_rechaza_un_puntaje_de_un_criterio_de_otra_rubrica(): void
    {
        $jurado = $this->usuarioConRol('Jurado');
        $rubricas = Rubrica::with('criterios')->whereHas('criterios')->take(2)->get();

        if ($rubricas->count() < 2) {
            $this->markTestSkipped('Hacen falta al menos dos rúbricas con criterios en los datos de prueba.');
        }

        [$rubricaElegida, $otraRubrica] = $rubricas;
        $proyecto = \App\Models\Proyecto::whereDoesntHave('evaluaciones', fn ($q) => $q->where('jurado_id', $jurado->id))->firstOrFail();

        // Puntajes de la rúbrica elegida, pero con un criterio que en realidad
        // pertenece a la otra rúbrica — el escenario que dejaba el formulario
        // cuando el jurado cambiaba de rúbrica sin que se limpiara el estado.
        $puntajes = $rubricaElegida->criterios->mapWithKeys(fn ($c) => [$c->id => $c->puntaje_maximo])->all();
        $criterioAjeno = $otraRubrica->criterios->first();
        $puntajes[$criterioAjeno->id] = $criterioAjeno->puntaje_maximo;

        // El controlador lanza una excepción dentro de la transacción (el
        // mismo mecanismo que usa para un puntaje fuera de rango), así que
        // lo que importa comprobar es que no quedó nada guardado.
        try {
            $this->actingAs($jurado)->post('/evaluaciones', [
                'proyecto_id' => $proyecto->id,
                'rubrica_id' => $rubricaElegida->id,
                'puntajes' => $puntajes,
            ]);
        } catch (\Throwable $e) {
            // Se descarta a propósito: aquí sólo interesa el estado final de la base.
        }

        $this->assertFalse(
            Evaluacion::where('proyecto_id', $proyecto->id)->where('jurado_id', $jurado->id)->exists(),
            'Se guardó una evaluación con un puntaje de un criterio que no pertenece a la rúbrica elegida.'
        );
    }
}
