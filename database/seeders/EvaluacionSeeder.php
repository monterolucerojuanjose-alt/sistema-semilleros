<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Evaluacion;
use App\Models\DetalleEvaluacion;
use App\Models\Proyecto;
use App\Models\User;
use App\Models\Rubrica;

class EvaluacionSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Obtener proyectos aprobados
        $proyectos = Proyecto::where('estado', 'Aprobado')->get();

        if ($proyectos->isEmpty()) {
            $this->command->info('No hay proyectos aprobados para crear evaluaciones.');
            return;
        }

        // Obtener usuarios con rol de Jurado
        $jurados = User::whereHas('roles', function ($query) {
            $query->where('name', 'Jurado');
        })->get();

        if ($jurados->isEmpty()) {
            $this->command->info('No hay usuarios con rol de Jurado para crear evaluaciones.');
            return;
        }

        // Obtener rúbricas
        $rubricas = Rubrica::with('criterios')->get();

        if ($rubricas->isEmpty()) {
            $this->command->info('No hay rúbricas disponibles para crear evaluaciones.');
            return;
        }

        $evaluacionesCreadas = 0;

        // Crear evaluaciones para algunos proyectos
        foreach ($proyectos->take(3) as $proyecto) {
            foreach ($jurados->take(2) as $jurado) {
                // Verificar si ya existe una evaluación para este proyecto y jurado
                $evaluacionExistente = Evaluacion::where('proyecto_id', $proyecto->id)
                    ->where('jurado_id', $jurado->id)
                    ->first();

                if ($evaluacionExistente) {
                    continue; // Saltar si ya existe
                }

                // Seleccionar una rúbrica aleatoria
                $rubrica = $rubricas->random();

                // Crear la evaluación
                $evaluacion = Evaluacion::create([
                    'proyecto_id' => $proyecto->id,
                    'jurado_id' => $jurado->id,
                    'rubrica_id' => $rubrica->id,
                    'observaciones' => $this->getObservacionAleatoria(),
                    'estado' => 'Evaluado',
                    'nota_final' => 0, // Se calculará después
                ]);

                // Crear detalles de evaluación
                $totalPuntuacion = 0;
                foreach ($rubrica->criterios as $criterio) {
                    // Generar puntaje aleatorio entre 1 y el máximo del criterio
                    $puntaje = rand(1, $criterio->puntaje_maximo);

                    DetalleEvaluacion::create([
                        'evaluacion_id' => $evaluacion->id,
                        'criterio_id' => $criterio->id,
                        'puntaje' => $puntaje,
                    ]);

                    // Calcular contribución a la nota final
                    $puntuacionNormalizada = ($puntaje / $criterio->puntaje_maximo) * 100;
                    $totalPuntuacion += $puntuacionNormalizada * ($criterio->peso / 100);
                }

                // Actualizar la nota final
                $evaluacion->update([
                    'nota_final' => round($totalPuntuacion, 2)
                ]);

                $evaluacionesCreadas++;
            }
        }

        $this->command->info("Se crearon {$evaluacionesCreadas} evaluaciones de prueba.");
    }

    /**
     * Obtener una observación aleatoria
     */
    private function getObservacionAleatoria(): string
    {
        $observaciones = [
            'Excelente trabajo, muy bien estructurado y con resultados significativos.',
            'Buen proyecto, aunque podría mejorar en algunas áreas metodológicas.',
            'Proyecto sólido con buenos fundamentos teóricos.',
            'Interesante propuesta, se recomienda profundizar más en el análisis.',
            'Trabajo bien desarrollado con aportes importantes al campo.',
            'Proyecto prometedor, se sugiere ampliar la muestra para mayor validez.',
            'Investigación bien fundamentada con resultados claros.',
            'Buen trabajo, se recomienda considerar más variables en futuras investigaciones.',
            'Proyecto innovador con potencial de aplicación práctica.',
            'Trabajo bien documentado, se sugiere mejorar la presentación de resultados.',
        ];

        return $observaciones[array_rand($observaciones)];
    }
}
