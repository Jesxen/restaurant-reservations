<?php

namespace App\Http\Resources;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Setting */
class SettingResource extends JsonResource
{
    /**
     * Full settings (admin view). Mirrors the nested shape consumed by the
     * frontend (see SettingController@publicShow) plus admin-only fields
     * (aforo, ticket_medio).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nombre_restaurante' => $this->nombre_restaurante,
            'aforo' => $this->aforo,
            'ticket_medio' => (float) $this->ticket_medio,

            'horarios' => [
                'comida' => substr((string) $this->apertura_comida, 0, 5).'–'.substr((string) $this->cierre_comida, 0, 5),
                'cena' => substr((string) $this->apertura_cena, 0, 5).'–'.substr((string) $this->cierre_cena, 0, 5),
            ],
            'horarios_detalle' => [
                'apertura_comida' => substr((string) $this->apertura_comida, 0, 5),
                'cierre_comida' => substr((string) $this->cierre_comida, 0, 5),
                'apertura_cena' => substr((string) $this->apertura_cena, 0, 5),
                'cierre_cena' => substr((string) $this->cierre_cena, 0, 5),
            ],
            'dias_cierre' => $this->diasCierre(),
            'reservas' => [
                'intervalo_slots' => $this->intervaloSlots(),
                'antelacion_min_horas' => $this->antelacionMinHoras(),
                'max_personas_online' => $this->max_personas_online ?: Reserva::MAX_PERSONAS,
                'ventana_dias' => Reserva::VENTANA_RESERVA_DIAS,
            ],
            'branding' => [
                'logo_url' => $this->logo_url,
                'color_primario' => $this->color_primario,
                'color_acento' => $this->color_acento,
            ],
            'contacto' => [
                'email' => $this->email_contacto,
                'telefono' => $this->telefono,
                'direccion' => $this->direccion,
                'ciudad' => $this->ciudad,
            ],
            'coords' => [
                'lat' => $this->lat !== null ? (float) $this->lat : null,
                'lng' => $this->lng !== null ? (float) $this->lng : null,
            ],
            'social' => [
                'instagram' => $this->instagram_url,
                'facebook' => $this->facebook_url,
                'tiktok' => $this->tiktok_url,
            ],
            'galeria' => $this->galeria ?? [],
        ];
    }
}
