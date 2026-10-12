<?php

namespace Tests\Usabilidad;

/**
 * El sistema de reportes debe poder exportar en Excel y en PDF sin errores,
 * incluyendo el reporte de evaluaciones ahora que combina notas de 0 a 100 y
 * trae la modalidad de las propuestas de evento.
 */
class ReportesExportacionTest extends UsabilidadTestCase
{
    private function coordinador()
    {
        return $this->usuarioConRol('Coordinador');
    }

    /**
     * Tanto Excel::download() como el PDF transmiten el archivo generado sin
     * dejarlo accesible vía getContent() en las pruebas; lo verificable aquí
     * es que el servidor haya anunciado un archivo real (Content-Length) y
     * no una respuesta vacía o un error disfrazado de 200.
     */
    private function tamanoDelArchivo($respuesta): int
    {
        return (int) $respuesta->headers->get('Content-Length', 0);
    }

    public function test_el_reporte_de_evaluaciones_se_exporta_en_excel_y_pdf(): void
    {
        $coordinador = $this->coordinador();

        $excel = $this->actingAs($coordinador)->post('/reportes/exportar', [
            'tipo_reporte' => 'evaluaciones',
            'formato' => 'excel',
        ]);
        $excel->assertOk();
        $this->assertGreaterThan(0, $this->tamanoDelArchivo($excel), 'El Excel de evaluaciones salió vacío.');
        $this->assertStringContainsStringIgnoringCase(
            'spreadsheetml',
            $excel->headers->get('Content-Type'),
            'El Excel de evaluaciones no se descargó con el tipo de contenido correcto.'
        );

        $pdf = $this->actingAs($coordinador)->post('/reportes/exportar', [
            'tipo_reporte' => 'evaluaciones',
            'formato' => 'pdf',
        ]);
        $pdf->assertOk();
        $this->assertGreaterThan(0, $this->tamanoDelArchivo($pdf), 'El PDF de evaluaciones salió vacío.');
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'), 'La respuesta no se descargó como PDF.');
    }

    /**
     * La vista previa del reporte de evaluaciones debe traer la modalidad
     * de las propuestas de evento, no sólo la nota y el jurado.
     *
     * Los datos de prueba existentes son de antes de que «modalidad»
     * existiera, así que se crea aquí una propuesta evaluada con modalidad
     * en vez de depender de que alguna fila sembrada ya la tenga.
     */
    public function test_la_vista_previa_de_evaluaciones_trae_la_modalidad(): void
    {
        $coordinador = $this->coordinador();
        $jurado = $this->usuarioConRol('Jurado');
        $evento = \App\Models\Evento::first();
        $this->assertNotNull($evento, 'No hay eventos en los datos de prueba.');

        $estudiante = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->whereDoesntHave('participacionesEventos', fn ($q) => $q->where('evento_id', $evento->id))
            ->firstOrFail();

        $propuesta = \App\Models\EventoParticipante::create([
            'evento_id' => $evento->id,
            'user_id' => $estudiante->id,
            'modalidad' => \App\Support\CatalogosEvento::MODALIDADES[2],
            'area_participacion' => \App\Support\CatalogosEvento::AREAS_PARTICIPACION[2],
            'titulo_proyecto' => 'Propuesta para probar la vista previa de reportes',
            'descripcion_proyecto' => 'Prueba de usabilidad.',
            'estado' => 'aprobado',
            'evaluador_id' => $jurado->id,
            'estado_evaluacion' => 'evaluado',
            'nota_final' => 88.0,
            'fecha_evaluacion' => now(),
        ]);

        $respuesta = $this->actingAs($coordinador)->get('/reportes/preview?tipo_reporte=evaluaciones');
        $respuesta->assertOk();

        $datos = collect($respuesta->json('datos'));
        $this->assertTrue(
            $datos->contains(fn ($fila) => ($fila['modalidad'] ?? null) === $propuesta->modalidad),
            'La vista previa de evaluaciones no trae la modalidad de las propuestas de evento.'
        );
    }

    /**
     * Barrido rápido del resto de reportes: que ningún tipo rompa al
     * exportar, en ninguno de los dos formatos.
     */
    public function test_los_demas_reportes_tambien_exportan_sin_error(): void
    {
        $coordinador = $this->coordinador();

        foreach (['semilleros', 'proyectos', 'eventos', 'inscripciones', 'participantes'] as $tipo) {
            foreach (['excel', 'pdf'] as $formato) {
                $respuesta = $this->actingAs($coordinador)->post('/reportes/exportar', [
                    'tipo_reporte' => $tipo,
                    'formato' => $formato,
                ]);

                $respuesta->assertOk();
                $this->assertGreaterThan(0, $this->tamanoDelArchivo($respuesta), "El reporte «{$tipo}» en {$formato} salió vacío.");
            }
        }
    }
}
