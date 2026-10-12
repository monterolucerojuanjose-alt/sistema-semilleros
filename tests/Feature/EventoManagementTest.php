<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_or_coordinator_can_store_events(): void
    {
        $student = $this->createUserWithRole('Estudiante');

        $response = $this->actingAs($student)->post(route('eventos.store'), [
            'titulo' => 'Evento restringido',
            'descripcion' => 'Descripcion valida para el evento.',
            'precio' => 0,
            'fecha_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'estado' => 'activo',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('eventos', ['titulo' => 'Evento restringido']);
    }

    public function test_admin_can_store_events(): void
    {
        $admin = $this->createUserWithRole('Administrador');

        $response = $this->actingAs($admin)->post(route('eventos.store'), [
            'titulo' => 'Encuentro de Semilleros',
            'descripcion' => 'Evento institucional para presentar avances de investigacion.',
            'precio' => 0,
            'fecha_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'ubicacion' => 'Auditorio Central',
            'cupo_maximo' => 100,
            'estado' => 'activo',
        ]);

        $response->assertRedirect(route('eventos.index'));
        $this->assertDatabaseHas('eventos', [
            'titulo' => 'Encuentro de Semilleros',
            'creado_por' => $admin->id,
        ]);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
