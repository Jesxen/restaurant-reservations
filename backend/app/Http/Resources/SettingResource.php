<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Setting */
class SettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nombre_restaurante' => $this->nombre_restaurante,
            'aforo' => $this->aforo,
            'apertura_comida' => substr((string) $this->apertura_comida, 0, 5),
            'cierre_comida' => substr((string) $this->cierre_comida, 0, 5),
            'apertura_cena' => substr((string) $this->apertura_cena, 0, 5),
            'cierre_cena' => substr((string) $this->cierre_cena, 0, 5),
            'duracion_turno' => $this->duracion_turno,
            'ticket_medio' => (float) $this->ticket_medio,
        ];
    }
}
