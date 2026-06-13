<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Day-before reminder sent to customers with an upcoming reservation.
 */
class RecordatorioReserva extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reserva $reserva) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de tu reserva de mañana — '.$this->reserva->localizador,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.recordatorio-reserva',
        );
    }
}
