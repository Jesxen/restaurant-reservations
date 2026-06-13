<?php

namespace App\Http\Requests\Admin;

use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservaRequest extends FormRequest
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
            'estado' => ['sometimes', 'required', Rule::in(Reserva::ESTADOS)],
            'mesa_id' => ['sometimes', 'nullable', 'integer', 'exists:mesas,id'],
            'notas' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notas_internas' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.in' => 'El estado seleccionado no es válido.',
            'mesa_id.exists' => 'La mesa seleccionada no existe.',
            'notas.max' => 'Las notas son demasiado largas.',
            'notas_internas.max' => 'Las notas internas son demasiado largas.',
        ];
    }
}
