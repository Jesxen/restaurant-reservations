<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MesaRequest extends FormRequest
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
        $mesaId = $this->route('mesa')?->id;

        return [
            'numero' => ['required', 'integer', 'min:1', Rule::unique('mesas', 'numero')->ignore($mesaId)],
            'capacidad' => ['required', 'integer', 'min:1', 'max:50'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }
}
