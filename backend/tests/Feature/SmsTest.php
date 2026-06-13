<?php

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_service_noops_cleanly_when_unconfigured(): void
    {
        $sms = new SmsService(sid: null, token: null, from: null);

        $this->assertFalse($sms->isConfigured());

        // Must not throw even though Twilio is not configured.
        $sms->send('+34123456789', 'Hola');

        $this->addToAssertionCount(1);
    }

    public function test_sms_is_sent_on_admin_confirmation_when_customer_has_phone(): void
    {
        // Spy that records send() calls instead of hitting Twilio.
        $spy = new class extends SmsService
        {
            /** @var array<int, array{to: string, message: string}> */
            public array $sent = [];

            public function __construct() {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function send(string $to, string $message): void
            {
                $this->sent[] = ['to' => $to, 'message' => $message];
            }
        };
        $this->app->instance(SmsService::class, $spy);

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client', 'phone' => '+34600123123']);
        $reserva = Reserva::factory()->create([
            'user_id' => $client->id,
            'fecha' => Carbon::today()->addDays(2)->toDateString(),
            'hora' => '21:00',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/reservas/{$reserva->id}", ['estado' => 'confirmada'])
            ->assertOk();

        $this->assertCount(1, $spy->sent);
        $this->assertSame('+34600123123', $spy->sent[0]['to']);
        $this->assertStringContainsString($reserva->localizador, $spy->sent[0]['message']);
    }

    public function test_no_sms_when_customer_has_no_phone(): void
    {
        $spy = new class extends SmsService
        {
            public int $calls = 0;

            public function __construct() {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function send(string $to, string $message): void
            {
                $this->calls++;
            }
        };
        $this->app->instance(SmsService::class, $spy);

        $admin = User::factory()->create(['role' => 'admin']);
        // Guest reservation (no linked user → no phone).
        $reserva = Reserva::factory()->create([
            'fecha' => Carbon::today()->addDays(2)->toDateString(),
            'hora' => '21:00',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/reservas/{$reserva->id}", ['estado' => 'confirmada'])
            ->assertOk();

        $this->assertSame(0, $spy->calls);
    }
}
