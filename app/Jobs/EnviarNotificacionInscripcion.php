<?php

namespace App\Jobs;

use App\Models\InscripcionEvento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\InscripcionConfirmacionMail;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionInscripcion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inscripcion;

    /**
     * Crea una nueva instancia de la tarea.
     */
    public function __construct(InscripcionEvento $inscripcion)
    {
        $this->inscripcion = $inscripcion;
    }

    /**
     * Ejecuta la tarea en segundo plano.
     */
    public function handle(): void
    {
        try {
            $inscripcion = $this->inscripcion->load(['proyecto.autor', 'proyecto.semillero']);

            Log::info('Enviando notificación de inscripción', [
                'codigo' => $inscripcion->codigo_inscripcion,
                'proyecto' => $inscripcion->proyecto->titulo,
                'autor' => $inscripcion->proyecto->autor->name,
                'email' => $inscripcion->proyecto->autor->email,
                'tipo' => $inscripcion->tipo,
            ]);

            // Enviar email de confirmación
            Mail::to($inscripcion->proyecto->autor->email)
                ->send(new InscripcionConfirmacionMail($inscripcion));

            Log::info('Notificación de inscripción enviada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de inscripción', [
                'inscripcion_id' => $this->inscripcion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
