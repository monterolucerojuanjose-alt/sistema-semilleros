<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\InscripcionEvento;
use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarCorreoInscripcionConfirmacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inscripcion;
    protected $estadoAnterior;

    /**
     * Crea una nueva instancia de la tarea.
     */
    public function __construct(InscripcionEvento $inscripcion, $estadoAnterior = null)
    {
        $this->inscripcion = $inscripcion;
        $this->estadoAnterior = $estadoAnterior;
    }

    /**
     * Ejecuta la tarea en segundo plano.
     */
    public function handle(): void
    {
        try {
            $usuario = $this->inscripcion->proyecto->autor ?? null;
            
            if (!$usuario) {
                Log::warning("Autor no encontrado para la inscripción: {$this->inscripcion->id}");
                return;
            }

            $titulo = '';
            $mensaje = '';
            $tipo = 'info';

            switch ($this->inscripcion->estado) {
                case 'Aceptado':
                    $titulo = '✅ ¡Inscripción Aceptada!';
                    $mensaje = "Tu inscripción para el evento ha sido aceptada. Tu proyecto '{$this->inscripcion->proyecto->titulo}' participará como {$this->inscripcion->tipo}.";
                    $tipo = 'exito';
                    break;
                case 'Rechazado':
                    $titulo = '❌ Inscripción Rechazada';
                    $mensaje = "Tu inscripción para el evento ha sido rechazada. Te invitamos a revisar los comentarios y hacer las correcciones necesarias.";
                    $tipo = 'error';
                    break;
                case 'En revisión':
                    $titulo = '📋 Inscripción en Revisión';
                    $mensaje = "Tu inscripción para el evento está siendo revisada por los coordinadores.";
                    $tipo = 'info';
                    break;
                case 'Inscrito':
                    $titulo = '📝 Inscripción Registrada';
                    $mensaje = "Tu inscripción para el evento ha sido registrada exitosamente. Tu proyecto '{$this->inscripcion->proyecto->titulo}' participará como {$this->inscripcion->tipo}.";
                    $tipo = 'info';
                    break;
                default:
                    $titulo = '📝 Estado de Inscripción Actualizado';
                    $mensaje = "El estado de tu inscripción ha sido actualizado a: {$this->inscripcion->estado}";
                    $tipo = 'info';
            }

            // Crear notificación interna
            Notificacion::crear(
                $usuario->id,
                $titulo,
                $mensaje,
                $tipo,
                [
                    'inscripcion_id' => $this->inscripcion->id,
                    'proyecto_id' => $this->inscripcion->proyecto_id,
                    'proyecto_titulo' => $this->inscripcion->proyecto->titulo ?? 'N/A',
                    'tipo_inscripcion' => $this->inscripcion->tipo,
                    'estado_anterior' => $this->estadoAnterior,
                    'estado_nuevo' => $this->inscripcion->estado,
                ]
            );

            // Enviar correo electrónico
            Mail::send('emails.inscripcion_estado', [
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'usuario' => $usuario,
                'inscripcion' => $this->inscripcion,
                'proyecto' => $this->inscripcion->proyecto,
                'estadoAnterior' => $this->estadoAnterior,
                'semillero' => $this->inscripcion->proyecto->semillero ?? null,
            ], function ($message) use ($usuario, $titulo) {
                $message->to($usuario->email, $usuario->name)
                        ->subject("🎯 {$titulo} - Sistema de Semilleros");
            });

            Log::info("Correo de estado de inscripción enviado exitosamente a {$usuario->email} para inscripción {$this->inscripcion->id}");

        } catch (\Exception $e) {
            Log::error("Error al enviar correo de estado de inscripción: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Se ejecuta si la tarea falla.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falló el envío de correo de estado de inscripción para inscripción {$this->inscripcion->id}: " . $exception->getMessage());
    }
}