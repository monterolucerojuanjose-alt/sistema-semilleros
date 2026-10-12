<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\Semillero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProyectoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_only_create_projects_in_approved_semilleros(): void
    {
        $student = $this->createUserWithRole('Estudiante');
        $tutor = $this->createUserWithRole('Docente/Tutor');
        $semillero = Semillero::create([
            'nombre' => 'Semillero Alfa',
            'descripcion' => 'Descripcion del semillero alfa',
            'linea_tematica' => 'Innovacion',
            'tutor_id' => $tutor->id,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($student)->post(route('proyectos.store'), [
            'titulo' => 'Proyecto no autorizado',
            'resumen' => str_repeat('Resumen valido ', 5),
            'semillero_id' => $semillero->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('proyectos', 0);
    }

    public function test_project_creation_generates_consecutive_codes(): void
    {
        $admin = $this->createUserWithRole('Administrador');
        $tutor = $this->createUserWithRole('Docente/Tutor');
        $semillero = Semillero::create([
            'nombre' => 'Semillero Beta',
            'descripcion' => 'Descripcion del semillero beta',
            'linea_tematica' => 'Investigacion aplicada',
            'tutor_id' => $tutor->id,
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)->post(route('proyectos.store'), [
            'titulo' => 'Primer proyecto',
            'resumen' => str_repeat('Resumen suficientemente largo. ', 3),
            'semillero_id' => $semillero->id,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('proyectos.store'), [
            'titulo' => 'Segundo proyecto',
            'resumen' => str_repeat('Otro resumen suficientemente largo. ', 3),
            'semillero_id' => $semillero->id,
        ])->assertRedirect();

        $codigos = Proyecto::orderBy('id')->pluck('codigo')->all();
        $year = now()->format('Y');

        $this->assertSame([
            "FAC-{$year}-001",
            "FAC-{$year}-002",
        ], $codigos);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
