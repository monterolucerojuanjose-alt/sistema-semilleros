<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Un usuario de demostración por cada rol (Spatie + columna users.role).
 * Ejecutar: php artisan db:seed --class=UnUsuarioPorRolSeeder
 */
class UnUsuarioPorRolSeeder extends Seeder
{
    private const PASSWORD_PLAIN = 'password';

    public function run(): void
    {
        $roles = [
            'Administrador',
            'Coordinador',
            'Docente/Tutor',
            'Estudiante',
            'Jurado',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $usuarios = [
            [
                'name' => 'Demo Administrador',
                'email' => 'admin.rol@umariana.edu.co',
                'role' => 'Administrador',
            ],
            [
                'name' => 'Demo Coordinador',
                'email' => 'coordinador.rol@umariana.edu.co',
                'role' => 'Coordinador',
            ],
            [
                'name' => 'Demo Docente Tutor',
                'email' => 'tutor.rol@umariana.edu.co',
                'role' => 'Docente/Tutor',
            ],
            [
                'name' => 'Demo Estudiante',
                'email' => 'estudiante.rol@umariana.edu.co',
                'role' => 'Estudiante',
            ],
            [
                'name' => 'Demo Jurado',
                'email' => 'jurado.rol@umariana.edu.co',
                'role' => 'Jurado',
            ],
        ];

        foreach ($usuarios as $data) {
            $roleName = $data['role'];

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make(self::PASSWORD_PLAIN),
                    'estado' => 'activo',
                    'role' => $roleName,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$roleName]);
        }

        $this->command?->newLine();
        $this->command?->info('Usuarios demo por rol (contraseña para todos: '.self::PASSWORD_PLAIN.'):');
        foreach ($usuarios as $u) {
            $this->command?->line("  • {$u['role']}: {$u['email']}");
        }
    }
}
