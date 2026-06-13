<?php

namespace App\Services;

use App\Mail\PlazaDisponible;
use App\Models\Reserva;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Mail;

/**
 * Coordinates the waitlist: deciding when a slot is full enough to justify
 * joining, and promoting the earliest waiting entry when seats free up after a
 * reservation is cancelled, declined or marked no-show.
 */
class WaitlistService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SmsService $sms,
    ) {}

    /**
     * Whether a party of this size can already be seated in the slot — if so the
     * customer should just book rather than join the waitlist.
     */
    public function slotHasSpace(string $fecha, string $hora, int $personas): bool
    {
        return $this->availability->canAccommodate($fecha, $hora, $personas);
    }

    /**
     * After seats free up for a slot, find the earliest still-waiting entry that
     * now fits and notify it (email + optional SMS), marking it 'notificado'.
     *
     * The freed reservation is excluded from the availability calculation so the
     * seats it just released are counted as free.
     */
    public function promoteForFreedSlot(Reserva $reserva): ?WaitlistEntry
    {
        $fecha = $reserva->fecha?->format('Y-m-d');
        $hora = substr((string) $reserva->hora, 0, 5);

        if ($fecha === null || $hora === '') {
            return null;
        }

        $entry = WaitlistEntry::query()
            ->whereDate('fecha', $fecha)
            ->where('hora', $reserva->hora)
            ->where('estado', 'esperando')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->first(fn (WaitlistEntry $e) => $this->availability->canAccommodate(
                $fecha,
                $hora,
                $e->personas,
                $reserva->id,
            ));

        if ($entry === null) {
            return null;
        }

        $entry->update(['estado' => 'notificado']);
        $this->notify($entry);

        return $entry;
    }

    /**
     * Send the "a seat is available" notification to a waitlist entry.
     */
    public function notify(WaitlistEntry $entry): void
    {
        Mail::to($entry->email)->send(new PlazaDisponible($entry));

        if (! empty($entry->telefono)) {
            $fecha = $entry->fecha?->format('d/m/Y');
            $hora = substr((string) $entry->hora, 0, 5);

            $this->sms->send(
                $entry->telefono,
                "¡Hay mesa libre para el {$fecha} a las {$hora}! Reserva ya en nuestra web antes de que se agote.",
            );
        }
    }
}
