<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Semillero;
use App\Models\User;

class SemilleroSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Obtener tutores disponibles
        $tutores = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Docente/Tutor', 'Coordinador']);
        })->get();

        if ($tutores->isEmpty()) {
            $this->command->warn('No hay tutores disponibles. Creando un tutor de prueba.');
            // Crear un tutor de prueba si no existe
            $tutor = User::create([
                'name' => 'Dr. Juan Pérez',
                'email' => 'tutor@universidad.edu',
                'password' => bcrypt('password123'),
                'estado' => 'activo',
            ]);
            $tutor->assignRole('Docente/Tutor');
            $tutorId = $tutor->id;
        } else {
            $tutorId = $tutores->first()->id;
        }

        $semilleros = [
            [
                'nombre' => 'Semillero de Inteligencia Artificial',
                'descripcion' => 'Investigación en técnicas de machine learning, deep learning y procesamiento de lenguaje natural para resolver problemas del mundo real.',
                'linea_tematica' => 'Inteligencia Artificial y Machine Learning',
                'tutor_id' => $tutorId,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Semillero de Desarrollo de Software',
                'descripcion' => 'Desarrollo de aplicaciones web y móviles, arquitecturas de software, metodologías ágiles y mejores prácticas de programación.',
                'linea_tematica' => 'Desarrollo de Software y Arquitecturas',
                'tutor_id' => $tutorId,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Semillero de Ciberseguridad',
                'descripcion' => 'Investigación en seguridad informática, análisis de vulnerabilidades, criptografía y protección de datos.',
                'linea_tematica' => 'Ciberseguridad y Protección de Datos',
                'tutor_id' => $tutorId,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Semillero de Internet de las Cosas',
                'descripcion' => 'Desarrollo de soluciones IoT, sensores inteligentes, conectividad y aplicaciones para ciudades inteligentes.',
                'linea_tematica' => 'Internet de las Cosas y Sistemas Embebidos',
                'tutor_id' => $tutorId,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Semillero de Análisis de Datos',
                'descripcion' => 'Técnicas de análisis estadístico, visualización de datos, big data y business intelligence.',
                'linea_tematica' => 'Ciencia de Datos y Análisis Estadístico',
                'tutor_id' => $tutorId,
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Semillero de Realidad Virtual',
                'descripcion' => 'Desarrollo de aplicaciones de realidad virtual y aumentada, interfaces inmersivas y experiencias interactivas.',
                'linea_tematica' => 'Realidad Virtual y Aumentada',
                'tutor_id' => $tutorId,
                'estado' => 'inactivo',
            ]
        ];

        foreach ($semilleros as $semilleroData) {
            Semillero::create($semilleroData);
        }

        $this->command->info('Semilleros creados exitosamente.');
    }
}
