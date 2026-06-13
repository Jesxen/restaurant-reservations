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
     * Accepts the nested settings shape used by the frontend (see
     * SettingResource / SettingController@publicShow). Read-only computed
     * fields (reservas.ventana_dias, horarios.*) are ignored on write.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hex = ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];

        return [
            'nombre_restaurante' => ['required', 'string', 'max:255'],
            'aforo' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'ticket_medio' => ['required', 'numeric', 'min:0', 'max:9999.99'],

            // Opening hours.
            'horarios_detalle.apertura_comida' => ['required', 'date_format:H:i'],
            'horarios_detalle.cierre_comida' => ['required', 'date_format:H:i'],
            'horarios_detalle.apertura_cena' => ['required', 'date_format:H:i'],
            'horarios_detalle.cierre_cena' => ['required', 'date_format:H:i'],

            // Booking config.
            'reservas.intervalo_slots' => ['required', 'integer', 'min:15', 'max:120'],
            'reservas.antelacion_min_horas' => ['required', 'integer', 'min:0', 'max:72'],
            'reservas.max_personas_online' => ['required', 'integer', 'min:1', 'max:100'],
            'dias_cierre' => ['nullable', 'array'],
            'dias_cierre.*' => ['integer', 'between:0,6'],

            // Branding.
            'branding.logo_url' => ['nullable', 'url', 'max:255'],
            'branding.color_primario' => $hex,
            'branding.color_acento' => $hex,

            // Contact.
            'contacto.email' => ['nullable', 'email', 'max:150'],
            'contacto.telefono' => ['nullable', 'string', 'max:40'],
            'contacto.direccion' => ['nullable', 'string', 'max:255'],
            'contacto.ciudad' => ['nullable', 'string', 'max:255'],

            // Coordinates.
            'coords.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'coords.lng' => ['nullable', 'numeric', 'between:-180,180'],

            // Social.
            'social.instagram' => ['nullable', 'url', 'max:255'],
            'social.facebook' => ['nullable', 'url', 'max:255'],
            'social.tiktok' => ['nullable', 'url', 'max:255'],

            // Gallery.
            'galeria' => ['nullable', 'array', 'max:24'],
            'galeria.*' => ['url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_restaurante.required' => 'El nombre del restaurante es obligatorio.',
            'horarios_detalle.apertura_comida.date_format' => 'La hora de apertura de comida no es válida.',
            'horarios_detalle.cierre_comida.date_format' => 'La hora de cierre de comida no es válida.',
            'horarios_detalle.apertura_cena.date_format' => 'La hora de apertura de cena no es válida.',
            'horarios_detalle.cierre_cena.date_format' => 'La hora de cierre de cena no es válida.',
            'reservas.intervalo_slots.min' => 'El intervalo entre reservas debe ser de al menos 15 minutos.',
            'reservas.antelacion_min_horas.min' => 'La antelación mínima no puede ser negativa.',
            'branding.color_primario.regex' => 'El color primario debe ser un código hexadecimal válido (ej. #1a2b3c).',
            'branding.color_acento.regex' => 'El color de acento debe ser un código hexadecimal válido (ej. #1a2b3c).',
            'contacto.email.email' => 'El correo de contacto no es válido.',
            'branding.logo_url.url' => 'La URL del logo no es válida.',
            'galeria.*.url' => 'Cada imagen de la galería debe ser una URL válida.',
            'dias_cierre.*.between' => 'Los días de cierre deben estar entre 0 (domingo) y 6 (sábado).',
        ];
    }
}
