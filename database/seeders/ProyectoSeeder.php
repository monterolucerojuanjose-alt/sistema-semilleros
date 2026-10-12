<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Semillero;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Hash;

class ProyectoSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $tutorUser = User::where('email', 'tutor@universidad.edu')->first()
            ?? User::where('email', 'admin@semilleros.edu')->first()
            ?? User::first();

        if (! $tutorUser) {
            throw new \RuntimeException('ProyectoSeeder: no hay usuarios tutor/admin; ejecuta antes RolesAndPermissionsSeeder y SemilleroSeeder.');
        }

        // Crear un semillero de prueba si no existe
        $semillero = Semillero::firstOrCreate(
            ['nombre' => 'Semillero de Prueba'],
            [
                'descripcion' => 'Semillero para pruebas del sistema de proyectos',
                'linea_tematica' => 'Tecnología e Innovación',
                'tutor_id' => $tutorUser->id,
                'estado' => 'activo'
            ]
        );

        // Crear un estudiante de prueba si no existe
        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@proyectos.edu'],
            [
                'name' => 'Estudiante Proyectos',
                'password' => Hash::make('password123'),
                'estado' => 'activo'
            ]
        );

        // Asignar rol de Estudiante
        if (!$estudiante->hasRole('Estudiante')) {
            $estudiante->assignRole('Estudiante');
        }

        // Crear relación entre estudiante y semillero
        if (!$estudiante->semilleros()->where('semillero_id', $semillero->id)->exists()) {
            $estudiante->semilleros()->attach($semillero->id, [
                'rol_en_semillero' => 'estudiante',
                'estado_solicitud' => 'aprobado',
                'fecha' => now(),
            ]);
        }

        // Crear algunos proyectos de prueba
        $proyectos = [
            [
                'titulo' => 'Proyecto de Investigación en IA',
                'resumen' => 'Este proyecto se enfoca en el desarrollo de algoritmos de inteligencia artificial para optimizar procesos industriales y mejorar la eficiencia en diferentes sectores de la economía.',
                'codigo' => 'FAC-2025-001',
                'estado' => 'Inscrito',
                'semillero_id' => $semillero->id,
                'autor_id' => $estudiante->id,
            ],
            [
                'titulo' => 'Desarrollo de Aplicaciones Móviles',
                'resumen' => 'Investigación sobre las mejores prácticas en el desarrollo de aplicaciones móviles nativas e híbridas, analizando rendimiento, experiencia de usuario y costos de desarrollo.',
                'codigo' => 'FAC-2025-002',
                'estado' => 'En revisión',
                'semillero_id' => $semillero->id,
                'autor_id' => $estudiante->id,
            ],
        ];

        foreach ($proyectos as $proyectoData) {
            Proyecto::firstOrCreate(
                ['codigo' => $proyectoData['codigo']],
                $proyectoData
            );
        }

        $this->command->info('Seeder de proyectos ejecutado exitosamente.');
        $this->command->info('Usuario estudiante: estudiante@proyectos.edu / password123');
        $this->command->info('Semillero creado: ' . $semillero->nombre);
    }
}
