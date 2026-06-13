<?php

namespace App\Http\Resources;

use App\Models\ReservaEvento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReservaEvento */
class ReservaEventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'usuario' => $this->whenLoaded('user', fn () => $this->user?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
