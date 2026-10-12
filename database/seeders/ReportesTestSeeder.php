<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Semillero;
use App\Models\Proyecto;
use App\Models\Evento;
use App\Models\InscripcionEvento;
use App\Models\EventoParticipante;
use App\Models\Evaluacion;
use App\Models\Rubrica;
use Illuminate\Support\Facades\Hash;

class ReportesTestSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creando datos de prueba para reportes...');

        // 1. Crear semilleros con diferentes líneas temáticas (programas)
        $this->command->info('📚 Creando semilleros...');

        $semilleros = [
            [
                'nombre' => 'Semillero de Inteligencia Artificial',
                'descripcion' => 'Investigación en IA y Machine Learning',
                'linea_tematica' => 'Ingeniería de Sistemas',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Semillero de Biotecnología',
                'descripcion' => 'Investigación en biotecnología aplicada',
                'linea_tematica' => 'Medicina',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Semillero de Robótica',
                'descripcion' => 'Desarrollo de sistemas robóticos',
                'linea_tematica' => 'Ingeniería Mecatrónica',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Semillero de Análisis de Datos',
                'descripcion' => 'Big Data y Analytics',
                'linea_tematica' => 'Ingeniería de Sistemas',
                'estado' => 'activo'
            ],
        ];

        // Obtener tutor existente o crear uno
        $tutor = User::whereHas('roles', function($q) {
            $q->where('name', 'Docente/Tutor');
        })->first();

        if (!$tutor) {
            $tutor = User::create([
                'name' => 'Dr. Carlos Martínez',
                'email' => 'carlos.martinez@universidad.edu',
                'password' => Hash::make('password'),
            ]);
            $tutor->assignRole('Docente/Tutor');
        }

        $semillerosCreados = [];
        foreach ($semilleros as $semilleroData) {
            $semilleroData['tutor_id'] = $tutor->id;
            $semillero = Semillero::firstOrCreate(
                ['nombre' => $semilleroData['nombre']],
                $semilleroData
            );
            $semillerosCreados[] = $semillero;
        }

        // 2. Crear estudiantes
        $this->command->info('👨‍🎓 Creando estudiantes...');

        $estudiantes = [];
        $nombresEstudiantes = [
            'Ana García López',
            'Juan Pérez Rodríguez',
            'María González Fernández',
            'Pedro Martínez Sánchez',
            'Laura Díaz Ramírez',
            'Carlos López Gómez',
            'Sofia Hernández Torres',
            'Miguel Ruiz Morales',
            'Elena Castro Ortiz',
            'David Romero Navarro',
            'Carmen Jiménez Cruz',
            'Luis Torres Vargas',
        ];

        foreach ($nombresEstudiantes as $index => $nombre) {
            $email = strtolower(str_replace(' ', '.', $nombre)) . '@estudiante.edu';

            $estudiante = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                ]
            );

            if (!$estudiante->hasRole('Estudiante')) {
                $estudiante->assignRole('Estudiante');
            }
            $estudiantes[] = $estudiante;
        }

        // 3. Crear proyectos para cada semillero
        $this->command->info('📁 Creando proyectos...');

        $proyectos = [];
        $titulosProyectos = [
            // Ingeniería de Sistemas (IA)
            ['Sistema de reconocimiento facial con deep learning', 0],
            ['Chatbot inteligente para atención al cliente', 0],
            ['Predicción de enfermedades usando ML', 0],

            // Medicina (Biotecnología)
            ['Desarrollo de biomateriales para implantes', 1],
            ['Análisis genético de enfermedades raras', 1],

            // Ingeniería Mecatrónica (Robótica)
            ['Robot autónomo para agricultura de precisión', 2],
            ['Brazo robótico para cirugía asistida', 2],
            ['Dron para monitoreo ambiental', 2],

            // Ingeniería de Sistemas (Análisis de Datos)
            ['Dashboard interactivo para análisis de ventas', 3],
            ['Sistema de recomendación personalizado', 3],
            ['Análisis predictivo de deserción estudiantil', 3],
        ];

        foreach ($titulosProyectos as $index => [$titulo, $semilleroIndex]) {
            $codigo = 'PROY-' . date('Y') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $proyecto = Proyecto::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'titulo' => $titulo,
                    'resumen' => 'Resumen del proyecto: ' . $titulo . '. Este proyecto busca innovar en el área correspondiente mediante la aplicación de tecnologías modernas y metodologías de investigación rigurosas.',
                    'semillero_id' => $semillerosCreados[$semilleroIndex]->id,
                    'autor_id' => $estudiantes[$index]->id,
                    'estado' => 'Aprobado',
                ]
            );
            $proyectos[] = $proyecto;

            // Inscribir estudiante al semillero (solo si no está ya inscrito)
            if (!$semillerosCreados[$semilleroIndex]->usuarios()->where('user_id', $estudiantes[$index]->id)->exists()) {
                $semillerosCreados[$semilleroIndex]->usuarios()->attach($estudiantes[$index]->id, [
                    'estado_solicitud' => 'aprobado',
                ]);
            }
        }

        // 4. Crear eventos
        $this->command->info('📅 Creando eventos...');

        $eventos = [
            [
                'titulo' => 'VI Congreso Nacional de Investigación 2025',
                'descripcion' => 'Congreso nacional donde se presentarán los mejores proyectos de investigación del año',
                'fecha_inicio' => now()->addDays(30),
                'fecha_fin' => now()->addDays(32),
                'ubicacion' => 'Auditorio Principal - Campus Universitario',
                'cupo_maximo' => 200,
                'estado' => 'activo',
                'precio' => 50000.00,
            ],
            [
                'titulo' => 'Feria de Ciencia y Tecnología 2025',
                'descripcion' => 'Feria regional de proyectos científicos y tecnológicos',
                'fecha_inicio' => now()->addDays(60),
                'fecha_fin' => now()->addDays(61),
                'ubicacion' => 'Centro de Convenciones',
                'cupo_maximo' => 150,
                'estado' => 'activo',
                'precio' => 30000.00,
            ],
        ];

        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Administrador');
        })->first();

        $eventosCreados = [];
        foreach ($eventos as $eventoData) {
            $eventoData['creado_por'] = $admin->id ?? $tutor->id;
            $evento = Evento::firstOrCreate(
                ['titulo' => $eventoData['titulo']],
                $eventoData
            );
            $eventosCreados[] = $evento;
        }

        // 5. Crear inscripciones de proyectos a eventos
        $this->command->info('📝 Creando inscripciones a eventos...');

        // Inscribir 8 proyectos al primer evento
        foreach (array_slice($proyectos, 0, 8) as $index => $proyecto) {
            InscripcionEvento::firstOrCreate(
                [
                    'proyecto_id' => $proyecto->id,
                    'evento_id' => $eventosCreados[0]->id,
                ],
                [
                    'tipo' => $index % 2 == 0 ? 'ponencia' : 'poster',
                    'estado' => 'Aceptado',
                    'codigo_inscripcion' => InscripcionEvento::generarCodigo(),
                    'observaciones' => 'Proyecto aceptado para presentación en el evento',
                ]
            );
        }

        // Inscribir 5 proyectos al segundo evento
        foreach (array_slice($proyectos, 3, 5) as $index => $proyecto) {
            InscripcionEvento::firstOrCreate(
                [
                    'proyecto_id' => $proyecto->id,
                    'evento_id' => $eventosCreados[1]->id,
                ],
                [
                    'tipo' => $index % 2 == 0 ? 'poster' : 'ponencia',
                    'estado' => $index < 3 ? 'Aceptado' : 'En revisión',
                    'codigo_inscripcion' => InscripcionEvento::generarCodigo(),
                    'observaciones' => $index < 3 ? 'Proyecto aceptado' : 'En proceso de revisión',
                ]
            );
        }

        // 6. Inscribir participantes (estudiantes) a eventos
        $this->command->info('👥 Inscribiendo participantes a eventos...');

        // Inscribir 10 estudiantes al primer evento
        foreach (array_slice($estudiantes, 0, 10) as $estudiante) {
            EventoParticipante::firstOrCreate(
                [
                    'evento_id' => $eventosCreados[0]->id,
                    'user_id' => $estudiante->id,
                ],
                [
                    'estado' => 'inscrito',
                ]
            );
        }

        // Inscribir 6 estudiantes al segundo evento
        foreach (array_slice($estudiantes, 2, 6) as $estudiante) {
            EventoParticipante::firstOrCreate(
                [
                    'evento_id' => $eventosCreados[1]->id,
                    'user_id' => $estudiante->id,
                ],
                [
                    'estado' => 'inscrito',
                ]
            );
        }

        // 7. Crear jurados
        $this->command->info('⚖️ Creando jurados...');

        $jurados = [];
        $nombresJurados = [
            ['Dr. Roberto Silva', 'roberto.silva@universidad.edu'],
            ['Dra. Patricia Mendoza', 'patricia.mendoza@universidad.edu'],
            ['Dr. Fernando Vargas', 'fernando.vargas@universidad.edu'],
        ];

        foreach ($nombresJurados as [$nombre, $email]) {
            $jurado = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make('password'),
                ]
            );

            if (!$jurado->hasRole('Jurado')) {
                $jurado->assignRole('Jurado');
            }
            $jurados[] = $jurado;
        }

        // 8. Crear rúbrica si no existe
        $this->command->info('📋 Creando rúbrica...');

        $rubrica = Rubrica::firstOrCreate(
            ['nombre' => 'Rúbrica General de Evaluación'],
            [
                'descripcion' => 'Rúbrica estándar para evaluación de proyectos de investigación',
            ]
        );

        // 9. Crear evaluaciones para proyectos del primer evento
        $this->command->info('⭐ Creando evaluaciones...');

        $proyectosEvento1 = array_slice($proyectos, 0, 8);

        foreach ($proyectosEvento1 as $index => $proyecto) {
            // Cada proyecto evaluado por 2 jurados
            for ($j = 0; $j < 2; $j++) {
                $jurado = $jurados[$j % count($jurados)];

                // Notas entre 3.5 y 5.0
                $nota = round(3.5 + (rand(0, 150) / 100), 2);

                Evaluacion::firstOrCreate(
                    [
                        'proyecto_id' => $proyecto->id,
                        'jurado_id' => $jurado->id,
                    ],
                    [
                        'rubrica_id' => $rubrica->id,
                        'nota_final' => $nota,
                        'estado' => 'Evaluado',
                        'observaciones' => 'Proyecto evaluado satisfactoriamente. ' .
                            ($nota >= 4.5 ? 'Excelente trabajo de investigación.' :
                            ($nota >= 4.0 ? 'Buen proyecto con oportunidades de mejora.' :
                            'Proyecto adecuado que cumple con los requisitos mínimos.')),
                    ]
                );
            }
        }

        $this->command->info('');
        $this->command->info('✅ ¡Datos de prueba creados exitosamente!');
        $this->command->info('');
        $this->command->info('📊 Resumen:');
        $this->command->info("   - Semilleros: " . count($semillerosCreados) . " (4 programas diferentes)");
        $this->command->info("   - Estudiantes: " . count($estudiantes));
        $this->command->info("   - Proyectos: " . count($proyectos));
        $this->command->info("   - Eventos: " . count($eventosCreados));
        $this->command->info("   - Inscripciones a eventos: " . InscripcionEvento::count());
        $this->command->info("   - Participantes inscritos: " . EventoParticipante::count());
        $this->command->info("   - Jurados: " . count($jurados));
        $this->command->info("   - Evaluaciones: " . Evaluacion::count());
        $this->command->info('');
        $this->command->info('🎯 Eventos creados:');
        foreach ($eventosCreados as $evento) {
            $this->command->info("   - {$evento->titulo}");
            $inscripciones = InscripcionEvento::where('evento_id', $evento->id)->count();
            $participantes = EventoParticipante::where('evento_id', $evento->id)->count();
            $this->command->info("     • Proyectos inscritos: {$inscripciones}");
            $this->command->info("     • Participantes: {$participantes}");
        }
        $this->command->info('');
        $this->command->info('📚 Programas académicos (líneas temáticas):');
        $this->command->info('   - Ingeniería de Sistemas: 6 proyectos');
        $this->command->info('   - Medicina: 2 proyectos');
        $this->command->info('   - Ingeniería Mecatrónica: 3 proyectos');
        $this->command->info('');
        $this->command->info('🔐 Credenciales de acceso:');
        $this->command->info('   Estudiantes: [nombre.completo]@estudiante.edu / password');
        $this->command->info('   Ejemplo: ana.garcia.lopez@estudiante.edu / password');
        $this->command->info('');
    }
}

