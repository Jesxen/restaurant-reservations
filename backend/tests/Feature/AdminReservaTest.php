<?php

namespace Tests\Feature;

use App\Mail\ReservaActualizada;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminReservaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_reservation_status_and_customer_is_notified(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $reserva = Reserva::factory()->create([
            'fecha' => Carbon::today()->addDays(2)->toDateString(),
            'hora' => '21:00',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/admin/reservas/{$reserva->id}", [
            'estado' => 'confirmada',
        ]);

        $response->assertOk()->assertJsonPath('data.estado', 'confirmada');
        $this->assertDatabaseHas('reserva_eventos', [
            'reserva_id' => $reserva->id,
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'confirmada',
        ]);
        Mail::assertSent(ReservaActualizada::class);
    }

    public function test_client_cannot_access_admin_reservas(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create(['estado' => 'pendiente']);

        $this->actingAs($client)
            ->patchJson("/api/admin/reservas/{$reserva->id}", ['estado' => 'confirmada'])
            ->assertForbidden();
    }
}
