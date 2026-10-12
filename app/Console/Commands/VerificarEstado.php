<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Semillero;
use App\Models\Proyecto;
use App\Models\InscripcionEvento;

class VerificarEstado extends Command
{
    protected $signature = 'sistema:estado';
    protected $description = 'Verificar el estado del sistema de semilleros';

    public function handle()
    {
        $this->info('🔍 Verificando estado del Sistema de Semilleros...');
        $this->newLine();

        // 1. Verificar conexión a base de datos
        $this->info('1️⃣ Verificando conexión a base de datos...');
        try {
            DB::connection()->getPdo();
            $this->info('   ✅ Conexión a base de datos: OK');
        } catch (\Exception $e) {
            $this->error('   ❌ Error de conexión: ' . $e->getMessage());
            return;
        }

        // 2. Verificar datos básicos
        $this->info('2️⃣ Verificando datos del sistema...');
        $usuarios = User::count();
        $semilleros = Semillero::count();
        $proyectos = Proyecto::count();
        $inscripciones = InscripcionEvento::count();

        $this->info("   📊 Usuarios registrados: {$usuarios}");
        $this->info("   📊 Semilleros creados: {$semilleros}");
        $this->info("   📊 Proyectos registrados: {$proyectos}");
        $this->info("   📊 Inscripciones al evento: {$inscripciones}");

        // 3. Verificar usuarios por rol
        $this->info('3️⃣ Verificando usuarios por rol...');
        $roles = ['Administrador', 'Coordinador', 'Docente/Tutor', 'Estudiante', 'Jurado'];

        foreach ($roles as $rol) {
            $count = User::role($rol)->count();
            $this->info("   👤 {$rol}: {$count} usuarios");
        }

        // 4. Verificar semilleros activos
        $this->info('4️⃣ Verificando semilleros activos...');
        $semillerosActivos = Semillero::where('estado', 'activo')->count();
        $this->info("   🏛️ Semilleros activos: {$semillerosActivos}");

        // 5. Verificar proyectos por estado
        $this->info('5️⃣ Verificando proyectos por estado...');
        $estadosProyectos = ['Inscrito', 'En revisión', 'Aprobado', 'Rechazado'];

        foreach ($estadosProyectos as $estado) {
            $count = Proyecto::where('estado', $estado)->count();
            $this->info("   📋 Proyectos {$estado}: {$count}");
        }

        // 6. Verificar inscripciones por estado
        $this->info('6️⃣ Verificando inscripciones por estado...');
        $estadosInscripciones = ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado'];

        foreach ($estadosInscripciones as $estado) {
            $count = InscripcionEvento::where('estado', $estado)->count();
            $this->info("   🎫 Inscripciones {$estado}: {$count}");
        }

        // 7. Verificar servicios
        $this->info('7️⃣ Verificando servicios...');
        $this->verificarServicios();

        $this->newLine();
        $this->info('✅ Verificación del sistema completada.');
        $this->newLine();
        $this->info('🌐 URLs disponibles:');
        $this->info('   • Aplicación: http://127.0.0.1:8000');
        $this->info('   • Vite Dev: http://localhost:5173');
        $this->info('   • Login: http://127.0.0.1:8000/login');
        $this->info('   • Dashboard: http://127.0.0.1:8000/dashboard');
    }

    private function verificarServicios()
    {
        // Verificar si el servidor Laravel está corriendo
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);

        try {
            $response = @file_get_contents('http://127.0.0.1:8000', false, $context);
            if ($response !== false) {
                $this->info('   ✅ Servidor Laravel: Activo');
            } else {
                $this->warn('   ⚠️ Servidor Laravel: No responde');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️ Servidor Laravel: Error de conexión');
        }

        // Verificar si Vite está corriendo
        try {
            $response = @file_get_contents('http://localhost:5173', false, $context);
            if ($response !== false) {
                $this->info('   ✅ Servidor Vite: Activo');
            } else {
                $this->warn('   ⚠️ Servidor Vite: No responde');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️ Servidor Vite: Error de conexión');
        }
    }
}
