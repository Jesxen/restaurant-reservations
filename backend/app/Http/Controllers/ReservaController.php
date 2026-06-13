<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateMisReservaRequest;
use App\Http\Resources\ReservaResource;
use App\Mail\NuevaReservaAdmin;
use App\Mail\ReservaRecibida;
use App\Models\Reserva;
use App\Models\ReservaEvento;
use App\Models\Setting;
use App\Services\StripeService;
use App\Services\WaitlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReservaController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly WaitlistService $waitlist,
    ) {}

    /**
     * Store a new reservation (guest or authenticated client).
     *
     * When deposits are enabled (admin setting + Stripe configured) a
     * PaymentIntent is created and its client_secret returned (additive field)
     * so the frontend can confirm payment. When deposits are off OR Stripe is
     * unconfigured, behaviour is identical to before.
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

        $setting = Setting::current();
        $cobrarDeposito = $setting->depositoActivo() && $this->stripe->isConfigured();

        if ($cobrarDeposito) {
            $data['deposito_estado'] = 'pendiente';
            $data['deposito_importe'] = round($setting->depositoPorPersona() * (int) $data['personas'], 2);
        } else {
            // Set explicitly so the returned model reflects it without a refresh.
            $data['deposito_estado'] = 'no_aplica';
        }

        $reserva = Reserva::create($data);

        // Attempt to create the Stripe PaymentIntent. If Stripe errors at this
        // point, fall back to a no-deposit reservation rather than failing the
        // whole booking.
        $clientSecret = null;

        if ($cobrarDeposito && $reserva->deposito_importe > 0) {
            try {
                $intent = $this->stripe->createDepositIntent($reserva, (float) $reserva->deposito_importe);
                $reserva->update(['payment_intent_id' => $intent['id']]);
                $clientSecret = $intent['client_secret'];
            } catch (Throwable $e) {
                Log::warning('No se pudo crear el PaymentIntent de Stripe; la reserva continúa sin depósito.', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                ]);
                $reserva->update(['deposito_estado' => 'no_aplica', 'deposito_importe' => null]);
            }
        }

        // Confirmation to the customer.
        Mail::to($reserva->email)->send(new ReservaRecibida($reserva));

        // Internal notification to the restaurant inbox.
        Mail::to(config('mail.contact_to'))->send(new NuevaReservaAdmin($reserva));

        return (new ReservaResource($reserva))
            ->additional([
                'message' => "¡Reserva recibida! Tu localizador es {$reserva->localizador}. Te confirmaremos en breve.",
                // Additive: null unless a deposit must be paid. The frontend
                // uses this with Stripe.js to confirm the card payment.
                'client_secret' => $clientSecret,
            ])
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
     * Client edits their own reservation (fecha/hora/personas/notas) while it is
     * still pendiente or confirmada. Editing a confirmada reservation resets it to
     * pendiente (re-confirmation) and records a status event.
     */
    public function actualizar(UpdateMisReservaRequest $request, Reserva $reserva): JsonResponse
    {
        $this->authorize('update', $reserva);

        $data = $request->validated();
        $estadoAnterior = $reserva->estado;

        // Re-confirmation: a confirmed reservation that changes goes back to pending.
        if ($estadoAnterior === 'confirmada') {
            $data['estado'] = 'pendiente';
        }

        $reserva->update($data);

        $mensaje = 'Reserva actualizada.';

        if ($estadoAnterior === 'confirmada' && $reserva->estado === 'pendiente') {
            ReservaEvento::create([
                'reserva_id' => $reserva->id,
                'user_id' => $request->user()->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'pendiente',
            ]);

            $mensaje = 'Reserva actualizada. Al haber cambios, volverá a quedar pendiente de confirmación.';
        }

        return (new ReservaResource($reserva->load('mesa')))
            ->additional(['message' => $mensaje])
            ->response();
    }

    /**
     * Cancel a reservation owned by the authenticated user.
     */
    public function cancelar(Request $request, Reserva $reserva): JsonResponse
    {
        $this->authorize('cancel', $reserva);

        $reserva->update(['estado' => 'cancelada']);

        // A seat just freed up: notify the earliest fitting waitlist entry.
        $this->waitlist->promoteForFreedSlot($reserva);

        return (new ReservaResource($reserva->load('mesa')))
            ->additional(['message' => 'Reserva cancelada.'])
            ->response();
    }
}
