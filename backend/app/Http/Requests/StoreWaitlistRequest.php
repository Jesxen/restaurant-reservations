<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use App\Models\Setting;
use App\Services\WaitlistService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Joining the waitlist for a full slot. Mirrors the reservation fields, but is
 * only accepted when the requested slot cannot currently seat the party (if it
 * can, the customer is told to just book).
 */
class StoreWaitlistRequest extends FormRequest
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
        $maxPersonas = Setting::current()->max_personas_online ?: Reserva::MAX_PERSONAS;

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(Reserva::VENTANA_RESERVA_DIAS)->toDateString()],
            'hora' => ['required', 'date_format:H:i'],
            'personas' => ['required', 'integer', 'min:1', 'max:'.$maxPersonas],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'fecha.required' => 'Debes seleccionar una fecha.',
            'hora.required' => 'Debes seleccionar una hora.',
            'hora.date_format' => 'La hora no es válida.',
            'personas.required' => 'El número de personas es obligatorio.',
            'personas.min' => 'El número de personas debe ser al menos 1.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Reject if seats are actually available: they should just book.
            $waitlist = app(WaitlistService::class);

            if ($waitlist->slotHasSpace((string) $this->input('fecha'), (string) $this->input('hora'), (int) $this->input('personas'))) {
                $validator->errors()->add(
                    'hora',
                    'Todavía hay disponibilidad para esa fecha y hora: puedes reservar directamente.',
                );
            }
        });
    }
}
