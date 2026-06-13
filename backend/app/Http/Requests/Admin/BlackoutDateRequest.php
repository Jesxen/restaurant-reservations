<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlackoutDateRequest extends FormRequest
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
        $blackout = $this->route('blackout_date');
        $id = is_object($blackout) ? $blackout->id : $blackout;

        return [
            'fecha' => [
                'required',
                'date',
                Rule::unique('blackout_dates', 'fecha')->ignore($id),
            ],
            'motivo' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'Debes indicar una fecha.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.unique' => 'Esa fecha ya está marcada como cerrada.',
            'motivo.max' => 'El motivo no puede superar los 120 caracteres.',
        ];
    }
}
