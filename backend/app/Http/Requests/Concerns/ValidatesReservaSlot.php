<?php

namespace App\Http\Requests\Concerns;

use App\Services\AvailabilityService;
use App\Services\SlotService;
use Illuminate\Contracts\Validation\Validator;

/**
 * Shared slot/calendar/availability validation for reservation create + edit.
 *
 * Expects the using FormRequest to expose fecha, hora and personas inputs, and
 * an optional reservaIgnorada() returning the id to exclude from availability
 * (so edits don't collide with themselves).
 */
trait ValidatesReservaSlot
{
    /**
     * Reservation id to ignore in availability checks (override on edit requests).
     */
    protected function reservaIgnorada(): ?int
    {
        return null;
    }

    protected function validarSlot(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $fecha = (string) $this->input('fecha');
        $hora = (string) $this->input('hora');
        $personas = (int) $this->input('personas');

        $slots = app(SlotService::class);

        // Closed weekday / blackout date.
        if ($motivo = $slots->motivoCierre($fecha)) {
            $validator->errors()->add('fecha', $motivo);

            return;
        }

        // The time must align to a generated slot within a service window.
        if (! $slots->esSlotValido($hora)) {
            $validator->errors()->add('hora', 'Esa hora no es un horario de reserva válido.');

            return;
        }

        // Respect minimum lead time / no past slots.
        if (! $slots->respetaAntelacion($fecha, $hora)) {
            $validator->errors()->add('hora', 'Esa hora ya no está disponible para reservar. Elige un horario posterior.');

            return;
        }

        // Availability / anti-overbooking (overlap-aware).
        $availability = app(AvailabilityService::class);
        $left = $availability->seatsLeft($fecha, $hora, $this->reservaIgnorada());

        if ($personas > $left) {
            $validator->errors()->add(
                'personas',
                $left > 0
                    ? "Sólo quedan {$left} plazas para esa fecha y hora."
                    : 'No hay disponibilidad para esa fecha y hora.'
            );
        }
    }
}
