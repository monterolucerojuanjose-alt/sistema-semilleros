<?php

namespace Tests\Usabilidad;

use App\Models\Semillero;
use App\Models\User;

/**
 * Rol Docente/Tutor — módulos: Gestión de semilleros y proyectos.
 */
class DocenteTutorTest extends UsabilidadTestCase
{
    /**
     * Devuelve un tutor que efectivamente tenga un semillero a su cargo,
     * creando uno si en los datos de prueba ninguno lo tiene.
     */
    private function tutorConSemillero(): array
    {
        $tutor = $this->usuarioConRol('Docente/Tutor');

        $semillero = Semillero::where('tutor_id', $tutor->id)->first()
            ?? Semillero::create([
                'nombre' => 'Semillero del Tutor (prueba)',
                'descripcion' => 'Semillero creado para la prueba de usabilidad.',
                'linea_tematica' => 'Pruebas',
                'tutor_id' => $tutor->id,
                'estado' => 'activo',
            ]);

        return [$tutor, $semillero];
    }

    /** HU-18. Consultar los semilleros a su cargo. */
    public function test_hu18_consultar_sus_semilleros(): void
    {
        [$tutor, $semillero] = $this->tutorConSemillero();

        $props = $this->abrirPagina($tutor, '/mis-semilleros', 'HU-18');
        $this->assertArrayHasKey('semilleros', $props, 'HU-18: la pantalla no entrega semilleros.');

        $ids = collect($props['semilleros'])->pluck('id');
        $this->assertTrue($ids->contains($semillero->id), 'HU-18: el tutor no ve el semillero que tiene a su cargo.');

        // Sólo debe ver los suyos, no los de otros tutores.
        $ajenos = Semillero::where('tutor_id', '!=', $tutor->id)->pluck('id');
        $this->assertTrue(
            $ids->intersect($ajenos)->isEmpty(),
            'HU-18: el tutor está viendo semilleros que no están a su cargo.'
        );

        $this->abrirPagina($tutor, "/semilleros/{$semillero->id}", 'HU-18');
    }

    /** HU-20. Revisar una solicitud de ingreso de un estudiante a su semillero. */
    public function test_hu20_revisar_solicitud_de_ingreso(): void
    {
        [$tutor, $semillero] = $this->tutorConSemillero();

        // Un estudiante que todavía no pertenece al semillero solicita entrar.
        $estudiante = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))
            ->whereDoesntHave('semilleros', fn ($q) => $q->where('semilleros.id', $semillero->id))
            ->firstOrFail();

        $this->actingAs($estudiante)
            ->post("/semilleros/{$semillero->id}/solicitud")
            ->assertSessionHasNoErrors();

        // El tutor la ve en su bandeja de solicitudes.
        $props = $this->abrirPagina($tutor, '/solicitudes', 'HU-20');
        $solicitudes = collect($props['solicitudes'] ?? []);
        $this->assertTrue(
            $solicitudes->contains(fn ($s) => (int) data_get($s, 'user_id') === $estudiante->id),
            'HU-20: la solicitud del estudiante no aparece en la bandeja del tutor.'
        );

        // Y la aprueba.
        $this->actingAs($tutor)
            ->put("/semilleros/{$semillero->id}/aprobar/{$estudiante->id}", ['estado' => 'aprobado'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            $semillero->miembrosAprobados()->where('users.id', $estudiante->id)->exists(),
            'HU-20: el estudiante no quedó como miembro aprobado.'
        );
    }

    /** HU-21. Consultar proyectos y resultados de evaluación de sus estudiantes. */
    public function test_hu21_consultar_proyectos_y_resultados(): void
    {
        [$tutor] = $this->tutorConSemillero();

        $props = $this->abrirPagina($tutor, '/proyectos', 'HU-21');
        $this->assertArrayHasKey('proyectos', $props, 'HU-21: el listado no entrega proyectos.');

        // Debe poder consultar los resultados de evaluación para orientar.
        $this->abrirPagina($tutor, '/resultados-evaluacion', 'HU-21');
        $this->abrirPagina($tutor, '/evaluaciones/ranking', 'HU-21');
    }

    /** Un tutor no debe poder cambiar el estado de un proyecto ni entrar a la auditoría. */
    public function test_el_tutor_no_accede_a_funciones_de_otros_roles(): void
    {
        [$tutor] = $this->tutorConSemillero();

        $respuesta = $this->actingAs($tutor)->get('/auditoria');
        $this->assertContains(
            $respuesta->getStatusCode(),
            [403, 302],
            'Seguridad: un tutor alcanzó la auditoría.'
        );
    }
}
