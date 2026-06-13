<?php

namespace Tests\Feature;

use App\Mail\PlazaDisponible;
use App\Models\Reserva;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WaitlistTest extends TestCase
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
            'dias_cierre' => [1], // Monday closed
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

    public function test_join_waitlist_is_rejected_when_seats_are_available(): void
    {
        $fecha = $this->fechaAbierta();

        $this->postJson('/api/waitlist', [
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
            'fecha' => $fecha,
            'hora' => '21:00',
            'personas' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors('hora');
    }

    public function test_can_join_waitlist_when_slot_is_full(): void
    {
        $fecha = $this->fechaAbierta();

        // Fill the 21:00 slot (capacity 20).
        Reserva::factory()->create([
            'fecha' => $fecha, 'hora' => '21:00', 'personas' => 20, 'estado' => 'confirmada',
        ]);

        $response = $this->postJson('/api/waitlist', [
            'nombre' => 'María',
            'email' => 'maria@example.com',
            'fecha' => $fecha,
            'hora' => '21:00',
            'personas' => 2,
        ]);

        $response->assertCreated()->assertJsonPath('data.estado', 'esperando');
        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'maria@example.com', 'estado' => 'esperando',
        ]);
    }

    public function test_cancelling_reservation_promotes_earliest_fitting_waitlist_entry(): void
    {
        Mail::fake();
        $fecha = $this->fechaAbierta();

        $client = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create([
            'user_id' => $client->id,
            'fecha' => $fecha, 'hora' => '21:00', 'personas' => 20, 'estado' => 'confirmada',
        ]);

        // Two waiting entries; earliest one fits the freed seats.
        $primero = WaitlistEntry::factory()->create([
            'fecha' => $fecha, 'hora' => '21:00', 'personas' => 4, 'estado' => 'esperando',
            'created_at' => now()->subMinutes(10),
        ]);
        $segundo = WaitlistEntry::factory()->create([
            'fecha' => $fecha, 'hora' => '21:00', 'personas' => 2, 'estado' => 'esperando',
            'created_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($client)
            ->patchJson("/api/reservas/{$reserva->id}/cancelar")
            ->assertOk();

        $this->assertDatabaseHas('waitlist_entries', ['id' => $primero->id, 'estado' => 'notificado']);
        $this->assertDatabaseHas('waitlist_entries', ['id' => $segundo->id, 'estado' => 'esperando']);
        Mail::assertSent(PlazaDisponible::class, 1);
    }

    public function test_admin_no_show_promotes_waitlist(): void
    {
        Mail::fake();
        $fecha = $this->fechaAbierta();

        $admin = User::factory()->create(['role' => 'admin']);
        $reserva = Reserva::factory()->create([
            'fecha' => $fecha, 'hora' => '20:00', 'personas' => 20, 'estado' => 'confirmada',
        ]);
        $entry = WaitlistEntry::factory()->create([
            'fecha' => $fecha, 'hora' => '20:00', 'personas' => 3, 'estado' => 'esperando',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/reservas/{$reserva->id}", ['estado' => 'no_show'])
            ->assertOk();

        $this->assertDatabaseHas('waitlist_entries', ['id' => $entry->id, 'estado' => 'notificado']);
        Mail::assertSent(PlazaDisponible::class, 1);
    }

    public function test_client_can_view_own_waitlist(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        WaitlistEntry::factory()->create(['user_id' => $client->id]);
        WaitlistEntry::factory()->create(); // someone else's

        $this->actingAs($client)->getJson('/api/mis-esperas')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_list_and_delete_waitlist_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $entry = WaitlistEntry::factory()->create();

        $this->actingAs($admin)->getJson('/api/admin/waitlist')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($admin)->deleteJson("/api/admin/waitlist/{$entry->id}")->assertOk();
        $this->assertDatabaseMissing('waitlist_entries', ['id' => $entry->id]);
    }
}
