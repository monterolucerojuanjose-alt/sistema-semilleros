<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Semillero;
use App\Models\Rubrica;
use App\Models\CriterioRubrica;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear usuario jurado
        $jurado = User::firstOrCreate(
            ['email' => 'jurado@semilleros.edu'],
            [
                'name' => 'Ana Jurado',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
            ]
        );
        $jurado->assignRole('Jurado');

        // 2. Crear usuario estudiante
        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@semilleros.edu'],
            [
                'name' => 'Carlos Estudiante',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
            ]
        );
        $estudiante->assignRole('Estudiante');

        // 3. Crear semillero
        $semillero = Semillero::firstOrCreate(
            ['nombre' => 'Semillero de Prueba'],
            [
                'descripcion' => 'Semillero para pruebas del sistema',
                'linea_tematica' => 'Tecnología',
                'estado' => 'activo',
                'tutor_id' => 1,
            ]
        );

        // 4. Crear proyectos de prueba
        $proyectos = [
            [
                'titulo' => 'Sistema de Gestión de Inventarios',
                'codigo' => 'PROY-001',
                'resumen' => 'Desarrollo de un sistema web para gestión de inventarios con tecnologías modernas.',
                'estado' => 'Aprobado',
                'autor_id' => $estudiante->id,
                'semillero_id' => $semillero->id,
            ],
            [
                'titulo' => 'Aplicación Móvil para Turismo',
                'codigo' => 'PROY-002',
                'resumen' => 'Aplicación móvil que facilita la planificación de viajes turísticos.',
                'estado' => 'Aprobado',
                'autor_id' => $estudiante->id,
                'semillero_id' => $semillero->id,
            ],
            [
                'titulo' => 'Algoritmo de Optimización de Rutas',
                'codigo' => 'PROY-003',
                'resumen' => 'Desarrollo de un algoritmo para optimizar rutas de entrega.',
                'estado' => 'Aprobado',
                'autor_id' => $estudiante->id,
                'semillero_id' => $semillero->id,
            ]
        ];

        foreach ($proyectos as $proyectoData) {
            Proyecto::firstOrCreate(
                ['codigo' => $proyectoData['codigo']],
                $proyectoData
            );
        }

        // 5. Crear rúbrica
        $rubrica = Rubrica::firstOrCreate(
            ['nombre' => 'Rúbrica de Evaluación 2025'],
            [
                'descripcion' => 'Rúbrica para evaluación de proyectos de investigación',
            ]
        );

        // 6. Crear criterios
        $criterios = [
            ['criterio' => 'Originalidad', 'peso' => 25, 'puntaje_maximo' => 5],
            ['criterio' => 'Metodología', 'peso' => 30, 'puntaje_maximo' => 5],
            ['criterio' => 'Resultados', 'peso' => 25, 'puntaje_maximo' => 5],
            ['criterio' => 'Presentación', 'peso' => 20, 'puntaje_maximo' => 5],
        ];

        foreach ($criterios as $criterioData) {
            CriterioRubrica::firstOrCreate(
                [
                    'rubrica_id' => $rubrica->id,
                    'criterio' => $criterioData['criterio']
                ],
                [
                    'peso' => $criterioData['peso'],
                    'puntaje_maximo' => $criterioData['puntaje_maximo']
                ]
            );
        }
    }
}




















