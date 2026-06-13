<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservaActualizada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reserva $reserva) {}

    public function envelope(): Envelope
    {
        $asunto = $this->reserva->estado === 'confirmada'
            ? 'Tu reserva está confirmada — '.$this->reserva->localizador
            : 'Tu reserva ha sido cancelada — '.$this->reserva->localizador;

        return new Envelope(subject: $asunto);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.reserva-actualizada',
        );
    }
}
