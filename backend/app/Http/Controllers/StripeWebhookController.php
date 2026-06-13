<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives Stripe webhook events (public, no auth, CSRF-exempt). Verifies the
 * signature with STRIPE_WEBHOOK_SECRET and reacts to payment_intent.succeeded
 * by marking the reservation deposit as paid.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): JsonResponse
    {
        // If Stripe isn't configured, there's nothing to verify against.
        if (! $stripe->isConfigured() || ! $stripe->hasWebhookSecret()) {
            return response()->json(['message' => 'Stripe no está configurado.'], 503);
        }

        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        try {
            $event = $stripe->constructWebhookEvent($payload, $signature);
        } catch (Throwable $e) {
            Log::warning('Webhook de Stripe rechazado.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Firma no válida.'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $this->marcarPagado($intent->id ?? null);
        }

        // Always 200 so Stripe stops retrying once we've accepted the event.
        return response()->json(['received' => true]);
    }

    /**
     * Mark the reservation tied to a PaymentIntent as deposit-paid. Estado is
     * deliberately left as-is (pendiente) so staff still confirm manually.
     */
    private function marcarPagado(?string $paymentIntentId): void
    {
        if ($paymentIntentId === null) {
            return;
        }

        Reserva::query()
            ->where('payment_intent_id', $paymentIntentId)
            ->where('deposito_estado', '!=', 'pagado')
            ->each(fn (Reserva $r) => $r->update(['deposito_estado' => 'pagado']));
    }
}
