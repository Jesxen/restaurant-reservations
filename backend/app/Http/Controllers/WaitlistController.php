<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaitlistRequest;
use App\Http\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WaitlistController extends Controller
{
    /**
     * Join the waitlist for a full slot (public; guests and clients).
     */
    public function store(StoreWaitlistRequest $request): JsonResponse
    {
        // /waitlist is public, so resolve any bearer token manually to link the
        // entry to a logged-in client.
        $user = $request->user() ?? auth('sanctum')->user();

        $data = $request->validated();
        $data['user_id'] = $user?->id;
        $data['estado'] = 'esperando';

        $entry = WaitlistEntry::create($data);

        return (new WaitlistEntryResource($entry))
            ->additional(['message' => 'Te hemos apuntado a la lista de espera. Si se libera una mesa, te avisaremos por correo'.(! empty($entry->telefono) ? ' y SMS.' : '.')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * The authenticated client's own waitlist entries.
     */
    public function misEsperas(Request $request): AnonymousResourceCollection
    {
        $entries = WaitlistEntry::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        return WaitlistEntryResource::collection($entries);
    }
}
