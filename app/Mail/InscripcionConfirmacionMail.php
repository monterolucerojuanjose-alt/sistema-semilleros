<?php

namespace App\Mail;

use App\Models\InscripcionEvento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscripcionConfirmacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public InscripcionEvento $inscripcion;

    /**
     * Crea una nueva instancia del correo.
     */
    public function __construct(InscripcionEvento $inscripcion)
    {
        $this->inscripcion = $inscripcion;
    }

    /**
     * Define el remitente y el asunto del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de Inscripción - ' . $this->inscripcion->codigo_inscripcion,
        );
    }

    /**
     * Define la plantilla y los datos del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.inscripcion-confirmacion',
            with: [
                'inscripcion' => $this->inscripcion,
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
