<?php

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\Setting;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Stripe\Event;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->updateOrCreate(['id' => 1], [
            'nombre_restaurante' => 'Test',
            'aforo' => 20,
            'apertura_comida' => '13:00',
            'cierre_comida' => '16:00',
            'apertura_cena' => '20:00',
            'cierre_cena' => '23:30',
            'duracion_turno' => 120,
            'ticket_medio' => 30,
            'intervalo_slots' => 30,
            'antelacion_min_horas' => 1,
            'max_personas_online' => 20,
            'dias_cierre' => [1],
        ]);
    }

    private function fechaAbierta(): string
    {
        $d = Carbon::today()->addDays(3);
        while ($d->dayOfWeek === Carbon::MONDAY) {
            $d->addDay();
        }

        return $d->toDateString();
    }

    public function test_deposit_disabled_reservation_is_unchanged_and_has_no_client_secret(): void
    {
        // Deposit off by default.
        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
            'fecha' => $this->fechaAbierta(),
            'hora' => '21:00',
            'personas' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.deposito.estado', 'no_aplica')
            ->assertJsonPath('client_secret', null);
    }

    public function test_deposit_enabled_reservation_returns_client_secret_with_faked_intent(): void
    {
        Setting::current()->update([
            'deposito_activo' => true,
            'deposito_por_persona' => 10,
        ]);

        // Fake Stripe so no real API call is made.
        $this->app->instance(StripeService::class, new class extends StripeService
        {
            public function __construct() {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function createDepositIntent(Reserva $reserva, float $amount): array
            {
                return [
                    'id' => 'pi_fake_123',
                    'client_secret' => 'pi_fake_123_secret_abc',
                    'amount' => (int) round($amount * 100),
                ];
            }
        });

        $response = $this->postJson('/api/reservas', [
            'nombre' => 'María',
            'email' => 'maria@example.com',
            'fecha' => $this->fechaAbierta(),
            'hora' => '21:00',
            'personas' => 3,
        ]);

        $response->assertCreated()
            ->assertJsonPath('client_secret', 'pi_fake_123_secret_abc')
            ->assertJsonPath('data.deposito.estado', 'pendiente')
            ->assertJsonPath('data.deposito.importe', 30);

        $this->assertDatabaseHas('reservas', [
            'email' => 'maria@example.com',
            'payment_intent_id' => 'pi_fake_123',
            'deposito_estado' => 'pendiente',
        ]);
    }

    public function test_public_settings_expose_deposito_block(): void
    {
        // `activo` is the EFFECTIVE state: admin toggle AND Stripe configured.
        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.key' => 'pk_test_fake']);
        Setting::current()->update(['deposito_activo' => true, 'deposito_por_persona' => 15]);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('deposito.activo', true)
            ->assertJsonPath('deposito.por_persona', 15)
            ->assertJsonPath('deposito.stripe_key', 'pk_test_fake');
    }

    public function test_public_settings_deposito_inactive_when_stripe_unconfigured(): void
    {
        // Admin enabled it but no Stripe secret → effective state is inactive.
        Setting::current()->update(['deposito_activo' => true, 'deposito_por_persona' => 15]);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('deposito.activo', false);
    }

    public function test_stripe_webhook_marks_deposit_paid_with_faked_service(): void
    {
        $reserva = Reserva::factory()->create([
            'payment_intent_id' => 'pi_hook_1',
            'deposito_estado' => 'pendiente',
            'deposito_importe' => 20,
        ]);

        $this->app->instance(StripeService::class, new class extends StripeService
        {
            public function __construct() {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function hasWebhookSecret(): bool
            {
                return true;
            }

            public function constructWebhookEvent(string $payload, string $signature): Event
            {
                return Event::constructFrom([
                    'type' => 'payment_intent.succeeded',
                    'data' => ['object' => ['id' => 'pi_hook_1']],
                ]);
            }
        });

        $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 't=1,v1=fake'])
            ->assertOk()
            ->assertJsonPath('received', true);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id, 'deposito_estado' => 'pagado',
        ]);
    }
}
