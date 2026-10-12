<?php

namespace Database\Seeders;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificacionSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $usuarios = User::all();

        if ($usuarios->isEmpty()) {
            $this->command->info('⚠️ No hay usuarios para crear notificaciones');
            return;
        }

        // Crear notificaciones de ejemplo para cada usuario
        foreach ($usuarios as $usuario) {
            // Notificaciones no leídas
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Bienvenido al Sistema',
                'mensaje' => 'Te damos la bienvenida al sistema de semilleros. ¡Explora todas las funcionalidades disponibles!',
                'tipo' => 'exito',
                'leida' => false,
                'datos_adicionales' => [
                    'tipo_bienvenida' => 'nuevo_usuario',
                    'fecha_registro' => now()->format('Y-m-d')
                ]
            ]);

            // Notificaciones leídas
            Notificacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Sistema Actualizado',
                'mensaje' => 'El sistema ha sido actualizado con nuevas funcionalidades. Revisa los cambios en el dashboard.',
                'tipo' => 'info',
                'leida' => true,
                'created_at' => now()->subDays(2)
            ]);

            // Notificaciones por tipo
            $tipos = ['info', 'exito', 'alerta', 'error'];
            foreach ($tipos as $tipo) {
                Notificacion::create([
                    'usuario_id' => $usuario->id,
                    'titulo' => $this->getTituloPorTipo($tipo),
                    'mensaje' => $this->getMensajePorTipo($tipo),
                    'tipo' => $tipo,
                    'leida' => rand(0, 1) == 1, // Aleatorio
                    'created_at' => now()->subDays(rand(1, 7))
                ]);
            }
        }

        // Crear notificaciones específicas para administradores
        $administradores = User::whereHas('roles', function($query) {
            $query->where('name', 'Administrador');
        })->get();

        foreach ($administradores as $admin) {
            Notificacion::create([
                'usuario_id' => $admin->id,
                'titulo' => 'Reporte Semanal Generado',
                'mensaje' => 'El reporte semanal del sistema ha sido generado automáticamente. Puedes descargarlo desde la sección de reportes.',
                'tipo' => 'info',
                'leida' => false,
                'datos_adicionales' => [
                    'reporte_tipo' => 'semanal',
                    'fecha_inicio' => now()->subWeek()->format('Y-m-d'),
                    'fecha_fin' => now()->format('Y-m-d')
                ]
            ]);
        }

        // Crear notificaciones específicas para coordinadores
        $coordinadores = User::whereHas('roles', function($query) {
            $query->where('name', 'Coordinador');
        })->get();

        foreach ($coordinadores as $coord) {
            Notificacion::create([
                'usuario_id' => $coord->id,
                'titulo' => 'Nuevas Solicitudes de Semillero',
                'mensaje' => 'Hay nuevas solicitudes de estudiantes para unirse a los semilleros. Revisa la sección de solicitudes.',
                'tipo' => 'alerta',
                'leida' => false,
                'datos_adicionales' => [
                    'solicitudes_pendientes' => rand(1, 5),
                    'prioridad' => 'media'
                ]
            ]);
        }

        $this->command->info('✅ Notificaciones de prueba creadas exitosamente');
    }

    private function getTituloPorTipo($tipo)
    {
        return match($tipo) {
            'info' => 'Información del Sistema',
            'exito' => 'Operación Exitosa',
            'alerta' => 'Atención Requerida',
            'error' => 'Error en el Sistema',
            default => 'Notificación'
        };
    }

    private function getMensajePorTipo($tipo)
    {
        return match($tipo) {
            'info' => 'Esta es una notificación informativa sobre el estado actual del sistema.',
            'exito' => 'La operación se ha completado exitosamente. ¡Todo está funcionando correctamente!',
            'alerta' => 'Se requiere tu atención para completar una acción pendiente en el sistema.',
            'error' => 'Se ha detectado un problema en el sistema. Por favor, contacta al administrador.',
            default => 'Notificación del sistema'
        };
    }
}