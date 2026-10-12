<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Evento;
use App\Models\InscripcionEvento;
use App\Models\Proyecto;
use App\Models\User;

class InscripcionEventoSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Obtener proyectos aprobados
        $proyectos = Proyecto::where('estado', 'Aprobado')->get();

        if ($proyectos->isEmpty()) {
            $this->command->info('No hay proyectos aprobados para crear inscripciones. Actualizando algunos proyectos a estado Aprobado...');

            // Actualizar algunos proyectos a estado Aprobado
            Proyecto::where('estado', 'Inscrito')->limit(2)->update(['estado' => 'Aprobado']);
            $proyectos = Proyecto::where('estado', 'Aprobado')->get();
        }

        $this->command->info('Proyectos aprobados encontrados: ' . $proyectos->count());

        // Toda inscripción pertenece a un evento: sin este dato el filtro por
        // evento del módulo de reportes no devuelve resultados.
        $evento = Evento::where('titulo', 'like', '%Encuentro de Semilleros%')->first()
            ?? Evento::orderBy('fecha_inicio')->first();

        if (! $evento) {
            $this->command->warn('No hay eventos registrados; se omite la creación de inscripciones.');
            return;
        }

        foreach ($proyectos as $proyecto) {
            // Solo crear inscripción si no existe una
            if (!$proyecto->inscripcionEvento) {
                $tipos = ['ponencia', 'poster'];
                $estados = ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado'];

                $inscripcion = InscripcionEvento::create([
                    'proyecto_id' => $proyecto->id,
                    'evento_id' => $evento->id,
                    'tipo' => fake()->randomElement($tipos),
                    'estado' => fake()->randomElement($estados),
                    'observaciones' => fake()->randomElement($estados) === 'Rechazado'
                        ? fake()->sentence(10)
                        : (fake()->randomElement($estados) === 'Aceptado'
                            ? fake()->sentence(8)
                            : null),
                    'codigo_inscripcion' => InscripcionEvento::generarCodigo(),
                ]);

                $this->command->info("Inscripción creada: {$inscripcion->codigo_inscripcion} para proyecto: {$proyecto->titulo}");
            }
        }

        $this->command->info('✅ Seeder de inscripciones completado exitosamente');
    }
}
