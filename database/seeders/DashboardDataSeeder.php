<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Semillero;
use App\Models\Proyecto;
use App\Models\InscripcionEvento;
use App\Models\Evaluacion;
use App\Models\Certificado;
use App\Models\Rubrica;
use App\Models\CriterioRubrica;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DashboardDataSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $this->command->info('Creando datos adicionales para dashboard...');

        // Crear roles si no existen
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Docente/Tutor']);
        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Jurado']);

        // Crear usuarios adicionales para estadísticas
        $this->crearUsuariosAdicionales();

        // Crear semilleros adicionales
        $this->crearSemillerosAdicionales();

        // Crear proyectos adicionales con diferentes estados
        $this->crearProyectosAdicionales();

        // Crear inscripciones adicionales
        $this->crearInscripcionesAdicionales();

        // Crear evaluaciones adicionales
        $this->crearEvaluacionesAdicionales();

        // Crear certificados adicionales
        $this->crearCertificadosAdicionales();

        $this->command->info('✅ Datos adicionales para dashboard creados exitosamente!');
    }

    private function crearUsuariosAdicionales()
    {
        $usuarios = [
            [
                'name' => 'María González',
                'email' => 'maria.gonzalez@estudiante.edu',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
                'role' => 'Estudiante'
            ],
            [
                'name' => 'Pedro Rodríguez',
                'email' => 'pedro.rodriguez@estudiante.edu',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
                'role' => 'Estudiante'
            ],
            [
                'name' => 'Ana López',
                'email' => 'ana.lopez@estudiante.edu',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
                'role' => 'Estudiante'
            ],
            [
                'name' => 'Dr. Carlos Ruiz',
                'email' => 'carlos.ruiz@docente.edu',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
                'role' => 'Docente/Tutor'
            ],
            [
                'name' => 'Dra. Laura Torres',
                'email' => 'laura.torres@jurado.edu',
                'password' => Hash::make('password123'),
                'estado' => 'activo',
                'role' => 'Jurado'
            ]
        ];

        foreach ($usuarios as $usuarioData) {
            $role = $usuarioData['role'];
            unset($usuarioData['role']);

            $usuario = User::firstOrCreate(
                ['email' => $usuarioData['email']],
                $usuarioData
            );

            if (!$usuario->hasRole($role)) {
                $usuario->assignRole($role);
            }
        }

        $this->command->info('✅ Usuarios adicionales creados');
    }

    private function crearSemillerosAdicionales()
    {
        $tutores = User::whereHas('roles', function($q) {
            $q->where('name', 'Docente/Tutor');
        })->get();

        $coordinadores = User::whereHas('roles', function($q) {
            $q->where('name', 'Coordinador');
        })->get();

        $semilleros = [
            [
                'nombre' => 'Semillero de Inteligencia Artificial',
                'descripcion' => 'Semillero enfocado en el desarrollo de soluciones de IA y machine learning.',
                'linea_tematica' => 'Inteligencia Artificial',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Semillero de Ciberseguridad',
                'descripcion' => 'Semillero dedicado a la investigación en seguridad informática y protección de datos.',
                'linea_tematica' => 'Ciberseguridad',
                'estado' => 'activo'
            ],
            [
                'nombre' => 'Semillero de Desarrollo Web',
                'descripcion' => 'Semillero enfocado en tecnologías web modernas y desarrollo de aplicaciones.',
                'linea_tematica' => 'Desarrollo Web',
                'estado' => 'activo'
            ]
        ];

        foreach ($semilleros as $index => $semilleroData) {
            $tutor = $tutores->get($index % $tutores->count());
            $coordinador = $coordinadores->first();

            $semilleroData['tutor_id'] = $tutor->id;
            if ($coordinador) {
                $semilleroData['coordinador_id'] = $coordinador->id;
            }

            Semillero::firstOrCreate(
                ['nombre' => $semilleroData['nombre']],
                $semilleroData
            );
        }

        $this->command->info('✅ Semilleros adicionales creados');
    }

    private function crearProyectosAdicionales()
    {
        $estudiantes = User::whereHas('roles', function($q) {
            $q->where('name', 'Estudiante');
        })->get();

        $semilleros = Semillero::all();

        $proyectos = [
            [
                'titulo' => 'Sistema de Reconocimiento Facial',
                'codigo' => 'PROY-004',
                'resumen' => 'Desarrollo de un sistema de reconocimiento facial usando deep learning para aplicaciones de seguridad.',
                'estado' => 'Aprobado'
            ],
            [
                'titulo' => 'Plataforma de E-commerce Segura',
                'codigo' => 'PROY-005',
                'resumen' => 'Creación de una plataforma de comercio electrónico con enfoque en seguridad y protección de datos.',
                'estado' => 'Aprobado'
            ],
            [
                'titulo' => 'Aplicación de Gestión de Tareas',
                'codigo' => 'PROY-006',
                'resumen' => 'Desarrollo de una aplicación web para gestión colaborativa de tareas y proyectos.',
                'estado' => 'En revisión'
            ],
            [
                'titulo' => 'Análisis de Sentimientos en Redes Sociales',
                'codigo' => 'PROY-007',
                'resumen' => 'Implementación de algoritmos de análisis de sentimientos para monitoreo de opinión pública.',
                'estado' => 'Aprobado'
            ],
            [
                'titulo' => 'Sistema de Monitoreo de Redes',
                'codigo' => 'PROY-008',
                'resumen' => 'Herramienta para monitoreo y análisis de tráfico de red en tiempo real.',
                'estado' => 'Rechazado'
            ]
        ];

        foreach ($proyectos as $index => $proyectoData) {
            $estudiante = $estudiantes->get($index % $estudiantes->count());
            $semillero = $semilleros->get($index % $semilleros->count());

            Proyecto::firstOrCreate(
                ['codigo' => $proyectoData['codigo']],
                [
                    ...$proyectoData,
                    'autor_id' => $estudiante->id,
                    'semillero_id' => $semillero->id,
                ]
            );
        }

        $this->command->info('✅ Proyectos adicionales creados');
    }

    private function crearInscripcionesAdicionales()
    {
        $proyectos = Proyecto::where('estado', 'Aprobado')->get();
        $tipos = ['ponencia', 'poster'];
        $estados = ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado'];

        foreach ($proyectos as $proyecto) {
            $cantidadInscripciones = rand(1, 3);

            for ($i = 0; $i < $cantidadInscripciones; $i++) {
                InscripcionEvento::firstOrCreate([
                    'proyecto_id' => $proyecto->id,
                    'tipo' => $tipos[array_rand($tipos)],
                    'estado' => $estados[array_rand($estados)],
                    'codigo_inscripcion' => 'INS-' . strtoupper(uniqid())
                ]);
            }
        }

        $this->command->info('✅ Inscripciones adicionales creadas');
    }

    private function crearEvaluacionesAdicionales()
    {
        $proyectos = Proyecto::where('estado', 'Aprobado')->get();
        $jurados = User::whereHas('roles', function($q) {
            $q->where('name', 'Jurado');
        })->get();

        $rubricas = Rubrica::all();

        if ($rubricas->isEmpty()) {
            // Crear rúbrica si no existe
            $rubrica = Rubrica::create([
                'nombre' => 'Rúbrica de Evaluación General',
                'descripcion' => 'Rúbrica estándar para evaluación de proyectos'
            ]);

            // Crear criterios
            $criterios = [
                ['criterio' => 'Originalidad', 'peso' => 25, 'puntaje_maximo' => 5],
                ['criterio' => 'Metodología', 'peso' => 30, 'puntaje_maximo' => 5],
                ['criterio' => 'Resultados', 'peso' => 30, 'puntaje_maximo' => 5],
                ['criterio' => 'Presentación', 'peso' => 15, 'puntaje_maximo' => 5],
            ];

            foreach ($criterios as $criterioData) {
                CriterioRubrica::create([
                    'rubrica_id' => $rubrica->id,
                    ...$criterioData
                ]);
            }

            $rubricas = collect([$rubrica]);
        }

        foreach ($proyectos as $proyecto) {
            $cantidadEvaluaciones = rand(1, 2);
            $juradosEvaluadores = $jurados->random($cantidadEvaluaciones);

            foreach ($juradosEvaluadores as $jurado) {
                // Verificar si ya existe evaluación
                $evaluacionExistente = Evaluacion::where('proyecto_id', $proyecto->id)
                    ->where('jurado_id', $jurado->id)
                    ->exists();

                if (!$evaluacionExistente) {
                    $rubrica = $rubricas->random();

                    Evaluacion::create([
                        'proyecto_id' => $proyecto->id,
                        'jurado_id' => $jurado->id,
                        'rubrica_id' => $rubrica->id,
                        'nota_final' => rand(70, 100),
                        'observaciones' => 'Evaluación realizada según criterios establecidos.',
                        'estado' => 'Evaluado'
                    ]);
                }
            }
        }

        $this->command->info('✅ Evaluaciones adicionales creadas');
    }

    private function crearCertificadosAdicionales()
    {
        $usuarios = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Estudiante', 'Jurado', 'Coordinador']);
        })->get();

        $proyectos = Proyecto::where('estado', 'Aprobado')->get();
        $tipos = ['Participante', 'Ponente', 'Jurado', 'Coordinador'];

        foreach ($usuarios->take(10) as $usuario) {
            $tipo = $tipos[array_rand($tipos)];
            $proyecto = $tipo === 'Participante' || $tipo === 'Ponente'
                ? $proyectos->random()
                : null;

            $codigoQr = 'CERT-' . strtoupper(uniqid());

            Certificado::firstOrCreate([
                'usuario_id' => $usuario->id,
                'proyecto_id' => $proyecto?->id,
                'tipo' => $tipo,
                'codigo_qr' => $codigoQr,
                'url_pdf' => 'certificados/certificado-' . $codigoQr . '.pdf',
                'fecha_emision' => now()->subDays(rand(1, 30))
            ]);
        }

        $this->command->info('✅ Certificados adicionales creados');
    }
}
