<?php

namespace App\Mail;

use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a waitlist entry when a seat frees up for its requested slot,
 * inviting the customer to book before someone else takes it.
 */
class PlazaDisponible extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WaitlistEntry $entry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Hay una mesa disponible para tu fecha!',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.plaza-disponible',
        );
    }
}
