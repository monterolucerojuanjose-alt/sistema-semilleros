<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\User;
use Carbon\Carbon;

class EventosSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Obtener el usuario admin para asignar como creador
        $admin = User::role('Administrador')->first();

        if (!$admin) {
            $this->command->warn('No se encontró un usuario administrador. Creando eventos sin creador...');
        }

        // Crear eventos de ejemplo
        $eventos = [
            [
                'titulo' => 'Congreso Nacional de Investigación Científica 2025',
                'descripcion' => 'Evento anual dedicado a la presentación de investigaciones científicas en todas las áreas del conocimiento. Incluye conferencias magistrales, paneles de discusión y presentación de pósters.',
                'precio' => 0,
                'fecha_inicio' => Carbon::now()->addDays(30),
                'fecha_fin' => Carbon::now()->addDays(32),
                'ubicacion' => 'Auditorio Principal - Campus Central',
                'cupo_maximo' => 100,
                'estado' => 'activo',
                'creado_por' => $admin?->id,
            ],
            [
                'titulo' => 'Workshop: Metodologías de Investigación Cualitativa',
                'descripcion' => 'Taller práctico sobre metodologías de investigación cualitativa. Aprenderás técnicas de entrevista, grupos focales, análisis de contenido y más. Incluye certificado de participación.',
                'precio' => 50000,
                'fecha_inicio' => Carbon::now()->addDays(15),
                'fecha_fin' => Carbon::now()->addDays(15)->addHours(6),
                'ubicacion' => 'Sala de Conferencias B - Edificio de Investigación',
                'cupo_maximo' => 30,
                'estado' => 'activo',
                'creado_por' => $admin?->id,
            ],
            [
                'titulo' => 'Simposio de Innovación y Emprendimiento',
                'descripcion' => 'Simposio enfocado en la innovación, el emprendimiento y la transferencia de conocimiento. Ideal para estudiantes interesados en comercializar sus investigaciones.',
                'precio' => 25000,
                'fecha_inicio' => Carbon::now()->addDays(45),
                'fecha_fin' => Carbon::now()->addDays(45)->addHours(8),
                'ubicacion' => 'Centro de Innovación',
                'cupo_maximo' => 50,
                'estado' => 'activo',
                'creado_por' => $admin?->id,
            ],
            [
                'titulo' => 'Encuentro de Semilleros de Investigación',
                'descripcion' => 'Espacio para que los diferentes semilleros de investigación compartan sus experiencias, metodologías y resultados. Incluye actividades de networking y colaboración interdisciplinaria.',
                'precio' => 0,
                'fecha_inicio' => Carbon::now()->addDays(20),
                'fecha_fin' => Carbon::now()->addDays(21),
                'ubicacion' => 'Plaza Central - Campus Universitario',
                'cupo_maximo' => null, // Sin límite de cupos
                'estado' => 'activo',
                'creado_por' => $admin?->id,
            ],
            [
                'titulo' => 'Conferencia: Tendencias en Inteligencia Artificial',
                'descripcion' => 'Conferencia magistral sobre las últimas tendencias en Inteligencia Artificial y Machine Learning aplicados a la investigación. Ponente invitado internacional.',
                'precio' => 30000,
                'fecha_inicio' => Carbon::now()->addDays(10),
                'fecha_fin' => Carbon::now()->addDays(10)->addHours(3),
                'ubicacion' => 'Auditorio de Ingeniería',
                'cupo_maximo' => 200,
                'estado' => 'activo',
                'creado_por' => $admin?->id,
            ],
            [
                'titulo' => 'Feria de Proyectos de Investigación 2024',
                'descripcion' => 'Evento ya finalizado donde se presentaron los mejores proyectos de investigación del año 2024.',
                'precio' => 0,
                'fecha_inicio' => Carbon::now()->subDays(60),
                'fecha_fin' => Carbon::now()->subDays(58),
                'ubicacion' => 'Plaza Principal',
                'cupo_maximo' => 150,
                'estado' => 'finalizado',
                'creado_por' => $admin?->id,
            ],
        ];

        foreach ($eventos as $eventoData) {
            $evento = Evento::create($eventoData);
            $this->command->info("Evento creado: {$evento->titulo}");
        }

        // Inscribir algunos estudiantes de ejemplo en los eventos activos
        $estudiantes = User::role('Estudiante')->limit(5)->get();
        $eventosActivos = Evento::activos()->get();

        if ($estudiantes->count() > 0 && $eventosActivos->count() > 0) {
            foreach ($eventosActivos as $evento) {
                // Inscribir aleatoriamente entre 1 y 3 estudiantes por evento
                $numEstudiantes = rand(1, min(3, $estudiantes->count()));
                $estudiantesInscritos = $estudiantes->random($numEstudiantes);

                foreach ($estudiantesInscritos as $estudiante) {
                    // Verificar que no esté ya inscrito
                    if (!EventoParticipante::where('evento_id', $evento->id)
                                          ->where('user_id', $estudiante->id)
                                          ->exists()) {
                        EventoParticipante::create([
                            'evento_id' => $evento->id,
                            'user_id' => $estudiante->id,
                            'estado' => 'inscrito',
                        ]);
                        $this->command->info("Estudiante {$estudiante->name} inscrito en {$evento->titulo}");
                    }
                }
            }
        }

        $this->command->info('Eventos y participantes creados exitosamente!');
    }
}










