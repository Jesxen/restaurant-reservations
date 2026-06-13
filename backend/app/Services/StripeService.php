<?php

namespace App\Services;

use App\Models\Reserva;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Wrapper around the Stripe SDK for reservation deposits.
 *
 * Degrades gracefully: when STRIPE_SECRET is absent isConfigured() is false and
 * callers skip deposit collection entirely, leaving reservations behaving
 * exactly as they did before deposits existed.
 *
 * Tests bind a fake of this class (or rely on isConfigured() === false) so the
 * real Stripe API is never contacted.
 */
class StripeService
{
    public function __construct(
        private readonly ?string $secret = null,
        private readonly ?string $webhookSecret = null,
        private readonly string $currency = 'eur',
    ) {}

    public function isConfigured(): bool
    {
        return ! empty($this->secret);
    }

    /**
     * Create a PaymentIntent for a reservation's deposit and return the array
     * the controller needs: ['id' => ..., 'client_secret' => ..., 'amount' => cents].
     *
     * @return array{id: string, client_secret: string, amount: int}
     */
    public function createDepositIntent(Reserva $reserva, float $amount): array
    {
        $cents = (int) round($amount * 100);

        $intent = $this->client()->paymentIntents->create([
            'amount' => $cents,
            'currency' => $this->currency,
            'metadata' => [
                'reserva_id' => (string) $reserva->id,
                'localizador' => (string) $reserva->localizador,
            ],
            'description' => "Depósito reserva {$reserva->localizador}",
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'amount' => $cents,
        ];
    }

    /**
     * Verify a webhook payload signature and return the constructed event.
     * Throws \UnexpectedValueException / SignatureVerificationException on
     * tampering, which the controller maps to a 400.
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, (string) $this->webhookSecret);
    }

    public function hasWebhookSecret(): bool
    {
        return ! empty($this->webhookSecret);
    }

    private function client(): StripeClient
    {
        return new StripeClient($this->secret);
    }

    /**
     * @return class-string<PaymentIntent>
     *
     * @internal Kept so static analysis sees the dependency used in tests.
     */
    public function paymentIntentClass(): string
    {
        return PaymentIntent::class;
    }
}
