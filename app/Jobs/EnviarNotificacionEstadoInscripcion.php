<?php

namespace App\Jobs;

use App\Models\InscripcionEvento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\EstadoInscripcionMail;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionEstadoInscripcion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inscripcion;
    protected $estadoAnterior;

    /**
     * Crea una nueva instancia de la tarea.
     */
    public function __construct(InscripcionEvento $inscripcion, string $estadoAnterior)
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
            $inscripcion = $this->inscripcion->load(['proyecto.autor', 'proyecto.semillero']);

            Log::info('Enviando notificación de cambio de estado de inscripción', [
                'codigo' => $inscripcion->codigo_inscripcion,
                'proyecto' => $inscripcion->proyecto->titulo,
                'autor' => $inscripcion->proyecto->autor->name,
                'email' => $inscripcion->proyecto->autor->email,
                'estado_anterior' => $this->estadoAnterior,
                'estado_nuevo' => $inscripcion->estado,
                'observaciones' => $inscripcion->observaciones,
            ]);

            // Enviar email de cambio de estado
            Mail::to($inscripcion->proyecto->autor->email)
                ->send(new EstadoInscripcionMail($inscripcion, $this->estadoAnterior));

            Log::info('Notificación de cambio de estado enviada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de cambio de estado', [
                'inscripcion_id' => $this->inscripcion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
