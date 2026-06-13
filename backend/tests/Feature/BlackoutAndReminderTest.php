<?php

namespace Tests\Feature;

use App\Mail\RecordatorioReserva;
use App\Models\BlackoutDate;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BlackoutAndReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_delete_blackout_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fecha = Carbon::today()->addDays(10)->toDateString();

        $create = $this->actingAs($admin)->postJson('/api/admin/blackout-dates', [
            'fecha' => $fecha,
            'motivo' => 'Reforma',
        ]);
        $create->assertCreated()->assertJsonPath('data.motivo', 'Reforma');

        $id = $create->json('data.id');

        $this->actingAs($admin)->deleteJson("/api/admin/blackout-dates/{$id}")->assertOk();
        $this->assertDatabaseMissing('blackout_dates', ['id' => $id]);
    }

    public function test_blackout_date_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fecha = Carbon::today()->addDays(10)->toDateString();
        BlackoutDate::create(['fecha' => $fecha, 'motivo' => 'X']);

        $this->actingAs($admin)->postJson('/api/admin/blackout-dates', ['fecha' => $fecha])
            ->assertStatus(422)->assertJsonValidationErrors('fecha');
    }

    public function test_client_cannot_manage_blackout_dates(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->postJson('/api/admin/blackout-dates', [
            'fecha' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertForbidden();
    }

    public function test_reminder_command_emails_next_day_reservations(): void
    {
        Mail::fake();

        $manana = Carbon::tomorrow()->toDateString();

        Reserva::factory()->create(['fecha' => $manana, 'estado' => 'confirmada']);
        Reserva::factory()->create(['fecha' => $manana, 'estado' => 'pendiente']);
        // Should NOT be reminded (cancelled + different day).
        Reserva::factory()->create(['fecha' => $manana, 'estado' => 'cancelada']);
        Reserva::factory()->create(['fecha' => Carbon::today()->addDays(3)->toDateString(), 'estado' => 'confirmada']);

        $this->artisan('reservas:recordatorios')->assertSuccessful();

        Mail::assertSent(RecordatorioReserva::class, 2);
    }
}
