<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesReservaSlot;
use App\Models\Reserva;
use App\Models\Setting;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Client-side edit of their own reservation. Authorization (ownership + editable
 * state) is handled by the controller via ReservaPolicy@update; this request
 * only validates the editable fields and re-checks slot/availability.
 */
class UpdateMisReservaRequest extends FormRequest
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

    protected function reservaIgnorada(): ?int
    {
        $reserva = $this->route('reserva');

        return $reserva instanceof Reserva ? $reserva->id : (int) $reserva;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validarSlot($validator));
    }
}
