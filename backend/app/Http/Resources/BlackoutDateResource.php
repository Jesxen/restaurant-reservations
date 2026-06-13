<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BlackoutDate */
class BlackoutDateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'motivo' => $this->motivo,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
