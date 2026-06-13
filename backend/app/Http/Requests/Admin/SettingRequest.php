<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
            'nombre_restaurante' => ['required', 'string', 'max:255'],
            'aforo' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'apertura_comida' => ['required', 'date_format:H:i'],
            'cierre_comida' => ['required', 'date_format:H:i'],
            'apertura_cena' => ['required', 'date_format:H:i'],
            'cierre_cena' => ['required', 'date_format:H:i'],
            'duracion_turno' => ['required', 'integer', 'min:30', 'max:480'],
            'ticket_medio' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}
