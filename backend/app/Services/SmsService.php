<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

/**
 * Thin wrapper around the Twilio SDK.
 *
 * Degrades gracefully: when Twilio is not configured (missing SID, token or
 * "from" number) every send() call is a silent no-op that logs a debug line.
 * This keeps the email flow working end-to-end without any Twilio credentials.
 */
class SmsService
{
    public function __construct(
        private readonly ?string $sid = null,
        private readonly ?string $token = null,
        private readonly ?string $from = null,
    ) {}

    /**
     * Whether all required Twilio credentials are present.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->sid) && ! empty($this->token) && ! empty($this->from);
    }

    /**
     * Send an SMS. No-ops (and logs) when unconfigured or when $to is empty.
     * Never throws to the caller — SMS is best-effort and must not break the
     * surrounding request (e.g. confirming a reservation).
     */
    public function send(string $to, string $message): void
    {
        if (trim($to) === '') {
            return;
        }

        if (! $this->isConfigured()) {
            Log::debug('SMS omitido: Twilio no está configurado.', ['to' => $to]);

            return;
        }

        try {
            $this->client()->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);
        } catch (Throwable $e) {
            // Best-effort: log and swallow so the customer-facing action succeeds.
            Log::warning('Fallo al enviar SMS por Twilio.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the Twilio client lazily so the SDK is never touched when
     * credentials are absent (and so tests can run without it).
     */
    private function client(): Client
    {
        return new Client($this->sid, $this->token);
    }
}
