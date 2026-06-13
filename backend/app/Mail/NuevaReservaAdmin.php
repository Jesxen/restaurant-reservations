<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification sent to the restaurant inbox when a new reservation
 * arrives, so staff can review and confirm it.
 */
class NuevaReservaAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reserva $reserva) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva reserva — '.$this->reserva->localizador,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.nueva-reserva-admin',
        );
    }
}
