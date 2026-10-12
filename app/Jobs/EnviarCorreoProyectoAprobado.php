<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Proyecto;
use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarCorreoProyectoAprobado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $proyecto;
    protected $estadoAnterior;

    /**
     * Crea una nueva instancia de la tarea.
     */
    public function __construct(Proyecto $proyecto, $estadoAnterior = null)
    {
        $this->proyecto = $proyecto;
        $this->estadoAnterior = $estadoAnterior;
    }

    /**
     * Ejecuta la tarea en segundo plano.
     */
    public function handle(): void
    {
        try {
            $usuario = $this->proyecto->autor;
            
            if (!$usuario) {
                Log::warning("Autor no encontrado para el proyecto: {$this->proyecto->id}");
                return;
            }

            $titulo = '';
            $mensaje = '';
            $tipo = 'info';

            switch ($this->proyecto->estado) {
                case 'Aprobado':
                    $titulo = '🎉 ¡Proyecto Aprobado!';
                    $mensaje = "Tu proyecto '{$this->proyecto->titulo}' ha sido aprobado exitosamente. ¡Felicitaciones!";
                    $tipo = 'exito';
                    break;
                case 'Rechazado':
                    $titulo = '❌ Proyecto Rechazado';
                    $mensaje = "Tu proyecto '{$this->proyecto->titulo}' ha sido rechazado. Te invitamos a revisar los comentarios y hacer las correcciones necesarias.";
                    $tipo = 'error';
                    break;
                case 'En revisión':
                    $titulo = '📋 Proyecto en Revisión';
                    $mensaje = "Tu proyecto '{$this->proyecto->titulo}' está siendo revisado por los evaluadores.";
                    $tipo = 'info';
                    break;
                default:
                    $titulo = '📝 Estado de Proyecto Actualizado';
                    $mensaje = "El estado de tu proyecto '{$this->proyecto->titulo}' ha sido actualizado a: {$this->proyecto->estado}";
                    $tipo = 'info';
            }

            // Crear notificación interna
            Notificacion::crear(
                $usuario->id,
                $titulo,
                $mensaje,
                $tipo,
                [
                    'proyecto_id' => $this->proyecto->id,
                    'proyecto_titulo' => $this->proyecto->titulo,
                    'proyecto_codigo' => $this->proyecto->codigo,
                    'estado_anterior' => $this->estadoAnterior,
                    'estado_nuevo' => $this->proyecto->estado,
                ]
            );

            // Enviar correo electrónico
            Mail::send('emails.proyecto_estado', [
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'usuario' => $usuario,
                'proyecto' => $this->proyecto,
                'estadoAnterior' => $this->estadoAnterior,
                'semillero' => $this->proyecto->semillero,
            ], function ($message) use ($usuario, $titulo) {
                $message->to($usuario->email, $usuario->name)
                        ->subject("📋 {$titulo} - Sistema de Semilleros");
            });

            Log::info("Correo de estado de proyecto enviado exitosamente a {$usuario->email} para proyecto {$this->proyecto->id}");

        } catch (\Exception $e) {
            Log::error("Error al enviar correo de estado de proyecto: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Se ejecuta si la tarea falla.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falló el envío de correo de estado de proyecto para proyecto {$this->proyecto->id}: " . $exception->getMessage());
    }
}