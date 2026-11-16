<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Datos de ejemplo para mostrar el sistema funcionando: usuarios de cada rol,
 * semilleros, proyectos, rúbricas, evaluaciones, eventos, certificados y
 * notificaciones.
 *
 * NO se ejecuta con `php artisan migrate --seed` (que deja el sistema en
 * blanco). Se carga a propósito:
 *
 *   php artisan db:seed --class=DatosDemostracionSeeder
 *
 * Las contraseñas de estos usuarios son públicas (están en el README): no
 * debe cargarse en una instalación real.
 */
class DatosDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Administrador de ejemplo: varios seeders de abajo lo usan como
        // creador de eventos y emisor de certificados.
        $administrador = User::firstOrCreate(
            ['email' => 'admin@semilleros.edu'],
            [
                'name' => 'Admin General',
                'password' => Hash::make('admin123'),
                'rol_id' => Role::where('name', 'Administrador')->value('id'),
                'estado' => 'activo',
            ]
        );
        $administrador->assignRole('Administrador');

        $this->call([
            // Un usuario de demostración por cada rol
            UnUsuarioPorRolSeeder::class,
            SemilleroSeeder::class,
            ProyectoSeeder::class,
            InscripcionEventoSeeder::class,

            // Rúbricas y evaluaciones
            RubricaSeeder::class,
            EvaluacionSeeder::class,
            TestDataSeeder::class,

            CertificadoSeeder::class,
            DashboardDataSeeder::class,
            NotificacionSeeder::class,
            EventosSeeder::class,
        ]);
    }
}
