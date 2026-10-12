<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionCorreo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $usuarioId;
    protected $titulo;
    protected $mensaje;
    protected $tipo;
    protected $datosAdicionales;

    /**
     * Crea una nueva instancia de la tarea.
     */
    public function __construct($usuarioId, $titulo, $mensaje, $tipo = 'info', $datosAdicionales = null)
    {
        $this->usuarioId = $usuarioId;
        $this->titulo = $titulo;
        $this->mensaje = $mensaje;
        $this->tipo = $tipo;
        $this->datosAdicionales = $datosAdicionales;
    }

    /**
     * Ejecuta la tarea en segundo plano.
     */
    public function handle(): void
    {
        try {
            $usuario = User::find($this->usuarioId);
            
            if (!$usuario) {
                Log::warning("Usuario no encontrado para envío de correo: {$this->usuarioId}");
                return;
            }

            // Crear notificación interna
            Notificacion::crear(
                $this->usuarioId,
                $this->titulo,
                $this->mensaje,
                $this->tipo,
                $this->datosAdicionales
            );

            // Enviar correo electrónico
            Mail::send('emails.notificacion', [
                'titulo' => $this->titulo,
                'mensaje' => $this->mensaje,
                'tipo' => $this->tipo,
                'usuario' => $usuario,
                'datosAdicionales' => $this->datosAdicionales,
            ], function ($message) use ($usuario, $titulo) {
                $message->to($usuario->email, $usuario->name)
                        ->subject("🔔 {$titulo} - Sistema de Semilleros");
            });

            Log::info("Correo de notificación enviado exitosamente a {$usuario->email}");

        } catch (\Exception $e) {
            Log::error("Error al enviar correo de notificación: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Se ejecuta si la tarea falla.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falló el envío de correo de notificación para usuario {$this->usuarioId}: " . $exception->getMessage());
    }
}