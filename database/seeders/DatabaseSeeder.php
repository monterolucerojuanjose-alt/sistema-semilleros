<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales de la aplicación.
     */
    public function run(): void
    {
        $this->call([
            // Roles, permisos y usuario administrador
            RolesAndPermissionsSeeder::class,
            // Un usuario de demostración por cada rol
            UnUsuarioPorRolSeeder::class,
            SemilleroSeeder::class,
            ProyectoSeeder::class,
            InscripcionEventoSeeder::class,

            // Nuevos seeders para evaluaciones
            RubricaSeeder::class,
            EvaluacionSeeder::class,

            // Datos de prueba para evaluaciones
            TestDataSeeder::class,

            // Certificados
            CertificadoSeeder::class,

            // Datos adicionales para dashboard
            DashboardDataSeeder::class,

            // Notificaciones de prueba
            NotificacionSeeder::class,

            // Eventos académicos
            EventosSeeder::class,
        ]);
    }
}
