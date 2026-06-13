<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservaRequest;
use App\Http\Resources\ReservaResource;
use App\Mail\ReservaRecibida;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;

class ReservaController extends Controller
{
    /**
     * Store a new reservation (guest or authenticated client).
     */
    public function store(StoreReservaRequest $request): JsonResponse
    {
        // /reservas is public, so the Sanctum middleware doesn't run. Resolve the
        // token manually so authenticated clients get the reservation linked to them.
        $user = $request->user() ?? auth('sanctum')->user();

        // Only whitelisted, validated fields are persisted. estado and user_id
        // are set explicitly here so guests can never inject them; mesa_id,
        // notas_internas, localizador are not in the validated set at all.
        $data = $request->validated();
        $data['user_id'] = $user?->id;
        $data['estado'] = 'pendiente';

        $reserva = Reserva::create($data);

        Mail::to($reserva->email)->send(new ReservaRecibida($reserva));

        return (new ReservaResource($reserva))
            ->additional(['message' => "¡Reserva recibida! Tu localizador es {$reserva->localizador}. Te confirmaremos en breve."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Reservations belonging to the authenticated user.
     */
    public function misReservas(Request $request): AnonymousResourceCollection
    {
        $reservas = $request->user()->reservas()
            ->with('mesa')
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        return ReservaResource::collection($reservas);
    }

    /**
     * Cancel a reservation owned by the authenticated user.
     */
    public function cancelar(Request $request, Reserva $reserva): JsonResponse
    {
        $this->authorize('cancel', $reserva);

        $reserva->update(['estado' => 'cancelada']);

        return (new ReservaResource($reserva->load('mesa')))
            ->additional(['message' => 'Reserva cancelada.'])
            ->response();
    }
}
