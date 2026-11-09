<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlantillaPruebaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $asuntoPrueba,
        public string $contenidoHtml
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asuntoPrueba,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->contenidoHtml,
        );
    }
}
