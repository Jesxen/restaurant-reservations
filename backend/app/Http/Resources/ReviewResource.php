<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'rating' => $this->rating,
            'comentario' => $this->comentario,
            'fecha' => $this->created_at?->toIso8601String(),
            // Staff-only moderation fields.
            'aprobada' => $this->when($request->user()?->isStaff() ?? false, $this->aprobada),
            'user_id' => $this->when($request->user()?->isStaff() ?? false, $this->user_id),
            'reserva_id' => $this->when($request->user()?->isStaff() ?? false, $this->reserva_id),
        ];
    }
}
