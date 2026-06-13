<?php

namespace App\Services;

use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Setting;

class AvailabilityService
{
    /**
     * Total seating capacity: the configured aforo if set, otherwise the sum
     * of active table capacities.
     */
    public function totalCapacity(): int
    {
        $aforo = Setting::current()->aforo;

        if ($aforo) {
            return (int) $aforo;
        }

        return (int) Mesa::query()->where('activa', true)->sum('capacidad');
    }

    /**
     * Seats already taken in a given date/time slot by active reservations.
     * Optionally ignore one reservation (e.g. when editing it).
     */
    public function seatsTaken(string $fecha, string $hora, ?int $ignoreReservaId = null): int
    {
        return (int) Reserva::query()
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->when($ignoreReservaId, fn ($q) => $q->where('id', '!=', $ignoreReservaId))
            ->sum('personas');
    }

    /**
     * Seats still available in a given slot.
     */
    public function seatsLeft(string $fecha, string $hora, ?int $ignoreReservaId = null): int
    {
        return max(0, $this->totalCapacity() - $this->seatsTaken($fecha, $hora, $ignoreReservaId));
    }

    /**
     * Whether a party of the given size fits in the slot.
     */
    public function canAccommodate(string $fecha, string $hora, int $personas, ?int $ignoreReservaId = null): bool
    {
        return $personas <= $this->seatsLeft($fecha, $hora, $ignoreReservaId);
    }
}
