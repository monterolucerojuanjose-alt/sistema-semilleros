<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationWithRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register_even_when_roles_have_not_been_seeded(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('roles', ['name' => 'Estudiante']);
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
