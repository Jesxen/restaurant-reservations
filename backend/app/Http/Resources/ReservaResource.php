<?php

namespace App\Http\Resources;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reserva */
class ReservaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'localizador' => $this->localizador,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'hora' => substr((string) $this->hora, 0, 5),
            'personas' => $this->personas,
            'estado' => $this->estado,
            'notas' => $this->notas,
            'notas_internas' => $this->when($request->user()?->isStaff() ?? false, $this->notas_internas),
            'mesa_id' => $this->mesa_id,
            'mesa' => new MesaResource($this->whenLoaded('mesa')),
            'user_id' => $this->user_id,
            'cancelable' => $this->cancelable(),
            'deposito' => [
                'estado' => $this->deposito_estado,
                'importe' => $this->deposito_importe !== null ? (float) $this->deposito_importe : null,
            ],
            'eventos' => ReservaEventoResource::collection($this->whenLoaded('eventos')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
