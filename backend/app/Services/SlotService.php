<?php

namespace App\Services;

use App\Models\BlackoutDate;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Generates bookable time slots and answers calendar/slot questions
 * (closed weekdays, blackout dates, past slots, slot alignment).
 *
 * Shared by HorarioController, StoreReservaRequest and UpdateMisReservaRequest
 * so reservation creation/editing and the public /horarios endpoint always
 * agree on what is bookable.
 */
class SlotService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Whether the given date falls on a configured closed weekday.
     */
    public function esDiaCierre(string $fecha): bool
    {
        $weekday = (int) Carbon::parse($fecha)->dayOfWeek; // 0=Sun..6=Sat

        return in_array($weekday, Setting::current()->diasCierre(), true);
    }

    /**
     * The blackout record for the date, if any (so callers can show the motivo).
     */
    public function blackout(string $fecha): ?BlackoutDate
    {
        return BlackoutDate::query()->whereDate('fecha', $fecha)->first();
    }

    /**
     * Human-readable Spanish reason the date is unavailable, or null if it is open.
     */
    public function motivoCierre(string $fecha): ?string
    {
        if ($this->esDiaCierre($fecha)) {
            $dia = $this->nombreDia((int) Carbon::parse($fecha)->dayOfWeek);

            return "Cerramos los {$dia}.";
        }

        if ($blackout = $this->blackout($fecha)) {
            $f = Carbon::parse($fecha)->format('d/m/Y');

            return $blackout->motivo
                ? "No abrimos el {$f} ({$blackout->motivo})."
                : "No abrimos el {$f}.";
        }

        return null;
    }

    /**
     * All candidate slot start times (H:i) for the service windows, stepped by
     * the configured interval. Does NOT filter past slots or availability.
     *
     * @return array<int, string>
     */
    public function generarHoras(): array
    {
        $s = Setting::current();
        $intervalo = $s->intervaloSlots();
        $horas = [];

        foreach ([['apertura_comida', 'cierre_comida'], ['apertura_cena', 'cierre_cena']] as [$abre, $cierra]) {
            if (empty($s->{$abre}) || empty($s->{$cierra})) {
                continue;
            }

            $inicio = $this->aMinutos((string) $s->{$abre});
            $fin = $this->aMinutos((string) $s->{$cierra});

            // Last seating must start no later than cierre (kitchen handles the turn).
            for ($m = $inicio; $m <= $fin; $m += $intervalo) {
                $horas[] = $this->aHora($m);
            }
        }

        return $horas;
    }

    /**
     * Whether a given H:i is one of the valid generated slots for the date.
     */
    public function esSlotValido(string $hora): bool
    {
        return in_array($this->normalizar($hora), $this->generarHoras(), true);
    }

    /**
     * Whether a slot on the given date is still bookable in time, i.e. it is in
     * the future and respects the minimum lead time (antelacion_min_horas).
     */
    public function respetaAntelacion(string $fecha, string $hora): bool
    {
        $slot = Carbon::parse($fecha.' '.$this->normalizar($hora));
        $minimo = Carbon::now()->addHours(Setting::current()->antelacionMinHoras());

        return $slot->greaterThanOrEqualTo($minimo);
    }

    /**
     * Build the public slot list for a date: each entry is
     * {hora, disponible, plazas_disponibles}. Returns an empty array when the
     * date is a closed weekday or a blackout date.
     *
     * @return array<int, array{hora: string, disponible: bool, plazas_disponibles: int}>
     */
    public function slotsParaFecha(string $fecha): array
    {
        if ($this->esDiaCierre($fecha) || $this->blackout($fecha)) {
            return [];
        }

        $slots = [];

        foreach ($this->generarHoras() as $hora) {
            if (! $this->respetaAntelacion($fecha, $hora)) {
                continue; // skip past slots / inside the lead-time window
            }

            $plazas = $this->availability->seatsLeft($fecha, $hora);

            $slots[] = [
                'hora' => $hora,
                'disponible' => $plazas > 0,
                'plazas_disponibles' => $plazas,
            ];
        }

        return $slots;
    }

    /**
     * Spanish weekday name (lowercase, plural-friendly) for a Carbon dayOfWeek.
     */
    private function nombreDia(int $weekday): string
    {
        return [
            0 => 'domingos',
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábados',
        ][$weekday] ?? 'ese día';
    }

    private function normalizar(string $hora): string
    {
        return substr($hora, 0, 5);
    }

    private function aMinutos(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    private function aHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
