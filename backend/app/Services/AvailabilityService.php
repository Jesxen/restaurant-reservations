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
     * Seats taken in the slot that starts at $hora on $fecha.
     *
     * A reservation occupies the half-open interval [hora, hora + duracion_turno).
     * The seats taken for the requested slot is the sum of personas of all active
     * reservations whose interval OVERLAPS the requested interval — not just those
     * starting at the exact same time. This prevents overbooking across turns.
     *
     * Two half-open intervals [aStart, aEnd) and [bStart, bEnd) overlap when
     * aStart < bEnd AND bStart < aEnd.
     *
     * Optionally ignore one reservation (e.g. when editing it).
     */
    public function seatsTaken(string $fecha, string $hora, ?int $ignoreReservaId = null): int
    {
        $duracion = (int) Setting::current()->duracion_turno;
        $inicio = $this->aMinutos($hora);
        $fin = $inicio + $duracion;

        return (int) Reserva::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->when($ignoreReservaId, fn ($q) => $q->where('id', '!=', $ignoreReservaId))
            ->get(['hora', 'personas'])
            ->filter(function (Reserva $r) use ($inicio, $fin, $duracion) {
                $rInicio = $this->aMinutos((string) $r->hora);
                $rFin = $rInicio + $duracion;

                // Overlap test for half-open intervals.
                return $inicio < $rFin && $rInicio < $fin;
            })
            ->sum('personas');
    }

    /**
     * Seats still available in the slot starting at $hora.
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

    /**
     * Normalise an "H:i" or "H:i:s" string to minutes since midnight.
     */
    private function aMinutos(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }
}
