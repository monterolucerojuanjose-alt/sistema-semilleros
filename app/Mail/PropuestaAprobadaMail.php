<?php

namespace App\Mail;

use App\Models\EventoParticipante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropuestaAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public EventoParticipante $participante;

    /**
     * Crea una nueva instancia del correo.
     */
    public function __construct(EventoParticipante $participante)
    {
        $this->participante = $participante;
    }

    /**
     * Define el remitente y el asunto del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu propuesta de proyecto ha sido aprobada! - ' . $this->participante->evento->titulo,
        );
    }

    /**
     * Define la plantilla y los datos del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta-aprobada',
            with: [
                'participante' => $this->participante,
                'evento' => $this->participante->evento,
                'estudiante' => $this->participante->usuario,
            ],
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
