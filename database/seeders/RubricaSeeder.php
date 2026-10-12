<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rubrica;
use App\Models\CriterioRubrica;

class RubricaSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Rúbrica para Ponencias
        $rubricaPonencias = Rubrica::create([
            'nombre' => 'Rúbrica de Evaluación para Ponencias 2025',
            'descripcion' => 'Rúbrica diseñada para evaluar presentaciones orales de proyectos de investigación en el encuentro institucional.',
        ]);

        // Criterios para rúbrica de ponencias
        $criteriosPonencias = [
            ['criterio' => 'Originalidad e Innovación', 'peso' => 25.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Claridad en la Presentación', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Metodología', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Resultados y Análisis', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Impacto y Aplicabilidad', 'peso' => 15.00, 'puntaje_maximo' => 5],
        ];

        foreach ($criteriosPonencias as $criterio) {
            CriterioRubrica::create([
                'rubrica_id' => $rubricaPonencias->id,
                'criterio' => $criterio['criterio'],
                'peso' => $criterio['peso'],
                'puntaje_maximo' => $criterio['puntaje_maximo'],
            ]);
        }

        // Rúbrica para Pósteres
        $rubricaPosteres = Rubrica::create([
            'nombre' => 'Rúbrica de Evaluación para Pósteres 2025',
            'descripcion' => 'Rúbrica diseñada para evaluar presentaciones visuales de proyectos de investigación.',
        ]);

        // Criterios para rúbrica de pósteres
        $criteriosPosteres = [
            ['criterio' => 'Diseño Visual y Estructura', 'peso' => 30.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Contenido Científico', 'peso' => 25.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Claridad de la Información', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Creatividad e Innovación', 'peso' => 15.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Calidad de la Presentación', 'peso' => 10.00, 'puntaje_maximo' => 5],
        ];

        foreach ($criteriosPosteres as $criterio) {
            CriterioRubrica::create([
                'rubrica_id' => $rubricaPosteres->id,
                'criterio' => $criterio['criterio'],
                'peso' => $criterio['peso'],
                'puntaje_maximo' => $criterio['puntaje_maximo'],
            ]);
        }

        // Rúbrica General para Proyectos
        $rubricaGeneral = Rubrica::create([
            'nombre' => 'Rúbrica General de Evaluación de Proyectos',
            'descripcion' => 'Rúbrica general para evaluar proyectos de investigación sin importar el tipo de presentación.',
        ]);

        // Criterios para rúbrica general
        $criteriosGeneral = [
            ['criterio' => 'Relevancia del Problema', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Marco Teórico', 'peso' => 15.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Metodología de Investigación', 'peso' => 25.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Análisis de Resultados', 'peso' => 20.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Conclusiones y Recomendaciones', 'peso' => 10.00, 'puntaje_maximo' => 5],
            ['criterio' => 'Calidad de Escritura', 'peso' => 10.00, 'puntaje_maximo' => 5],
        ];

        foreach ($criteriosGeneral as $criterio) {
            CriterioRubrica::create([
                'rubrica_id' => $rubricaGeneral->id,
                'criterio' => $criterio['criterio'],
                'peso' => $criterio['peso'],
                'puntaje_maximo' => $criterio['puntaje_maximo'],
            ]);
        }

        $this->command->info('Rúbricas y criterios creados exitosamente.');
    }
}
