<?php

namespace App\Mail;

use App\Models\InscripcionEvento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstadoInscripcionMail extends Mailable
{
    use Queueable, SerializesModels;

    public InscripcionEvento $inscripcion;
    public string $estadoAnterior;

    /**
     * Crea una nueva instancia del correo.
     */
    public function __construct(InscripcionEvento $inscripcion, string $estadoAnterior)
    {
        $this->inscripcion = $inscripcion;
        $this->estadoAnterior = $estadoAnterior;
    }

    /**
     * Define el remitente y el asunto del correo.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->inscripcion->estado) {
            'Aceptado' => 'Inscripción Aceptada - ' . $this->inscripcion->codigo_inscripcion,
            'Rechazado' => 'Inscripción Rechazada - ' . $this->inscripcion->codigo_inscripcion,
            default => 'Actualización de Estado - ' . $this->inscripcion->codigo_inscripcion,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Define la plantilla y los datos del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.estado-inscripcion',
            with: [
                'inscripcion' => $this->inscripcion,
                'estadoAnterior' => $this->estadoAnterior,
                'fecha' => now()->format('d/m/Y H:i:s'),
            ]
        );
    }

    /**
     * Define los archivos adjuntos del correo.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
