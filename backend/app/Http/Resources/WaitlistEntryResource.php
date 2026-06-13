<?php

namespace App\Http\Resources;

use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WaitlistEntry */
class WaitlistEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $staff = $request->user()?->isStaff() ?? false;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'hora' => substr((string) $this->hora, 0, 5),
            'personas' => $this->personas,
            'estado' => $this->estado,
            'created_at' => $this->created_at?->toIso8601String(),
            // Contact details are only exposed to staff and the owner themselves.
            'email' => $this->when($staff || $this->user_id === $request->user()?->id, $this->email),
            'telefono' => $this->when($staff || $this->user_id === $request->user()?->id, $this->telefono),
            'user_id' => $this->when($staff, $this->user_id),
        ];
    }
}
