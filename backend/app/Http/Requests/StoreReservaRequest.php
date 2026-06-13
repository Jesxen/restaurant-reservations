<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesReservaSlot;
use App\Models\Reserva;
use App\Models\Setting;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    use ValidatesReservaSlot;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxPersonas = Setting::current()->max_personas_online ?: Reserva::MAX_PERSONAS;

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(Reserva::VENTANA_RESERVA_DIAS)->toDateString()],
            'hora' => ['required', 'date_format:H:i'],
            'personas' => ['required', 'integer', 'min:1', 'max:'.$maxPersonas],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxPersonas = Setting::current()->max_personas_online ?: Reserva::MAX_PERSONAS;

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
            'personas.max' => "Para grupos de más de {$maxPersonas} personas, llámanos por teléfono y lo organizamos.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
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

            // Slot alignment, closed days, lead time and overlap-aware availability.
            $this->validarSlot($validator);
        });
    }
}
