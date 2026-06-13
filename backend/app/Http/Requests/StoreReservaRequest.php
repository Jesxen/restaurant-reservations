<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use App\Models\Setting;
use App\Services\AvailabilityService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(Reserva::VENTANA_RESERVA_DIAS)->toDateString()],
            'hora' => ['required', 'date_format:H:i'],
            'personas' => ['required', 'integer', 'min:1', 'max:'.Reserva::MAX_PERSONAS],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'fecha.required' => 'Debes seleccionar una fecha.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'fecha.before_or_equal' => 'Sólo aceptamos reservas con hasta '.Reserva::VENTANA_RESERVA_DIAS.' días de antelación.',
            'hora.required' => 'Debes seleccionar una hora.',
            'hora.date_format' => 'La hora no es válida.',
            'personas.required' => 'El número de personas es obligatorio.',
            'personas.min' => 'El número de personas debe ser al menos 1.',
            'personas.max' => 'Para grupos de más de '.Reserva::MAX_PERSONAS.' personas, llámanos por teléfono y lo organizamos.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Requested time must fall within a configured service window.
            if (! $this->dentroDelHorario($this->input('hora'))) {
                $validator->errors()->add('hora', 'Esa hora está fuera de nuestro horario de servicio.');

                return;
            }

            // Duplicate prevention (same name + date + time + party size).
            $duplicate = Reserva::query()
                ->where('nombre', $this->input('nombre'))
                ->where('fecha', $this->input('fecha'))
                ->where('hora', $this->input('hora'))
                ->where('personas', $this->input('personas'))
                ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('nombre', 'Ya existe una reserva con esos mismos datos. :(');

                return;
            }

            // Availability / anti-overbooking.
            $availability = app(AvailabilityService::class);
            $personas = (int) $this->input('personas');
            $left = $availability->seatsLeft($this->input('fecha'), $this->input('hora'));

            if ($personas > $left) {
                $validator->errors()->add(
                    'personas',
                    $left > 0
                        ? "Sólo quedan {$left} plazas para esa fecha y hora."
                        : 'No hay disponibilidad para esa fecha y hora.'
                );
            }
        });
    }

    /**
     * Whether the requested time falls within the comida or cena service window.
     */
    private function dentroDelHorario(string $hora): bool
    {
        $setting = Setting::current();
        $minutos = $this->aMinutos($hora);

        foreach ([['apertura_comida', 'cierre_comida'], ['apertura_cena', 'cierre_cena']] as [$abre, $cierra]) {
            if (empty($setting->{$abre}) || empty($setting->{$cierra})) {
                continue;
            }

            $inicio = $this->aMinutos((string) $setting->{$abre});
            $fin = $this->aMinutos((string) $setting->{$cierra});

            if ($minutos >= $inicio && $minutos <= $fin) {
                return true;
            }
        }

        return false;
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
