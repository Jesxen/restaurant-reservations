<?php

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservaEditTest extends TestCase
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
            'dias_cierre' => [],
        ]);
    }

    private function fecha(): string
    {
        return Carbon::today()->addDays(4)->toDateString();
    }

    public function test_owner_can_edit_pending_reservation(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create([
            'user_id' => $user->id,
            'fecha' => $this->fecha(),
            'hora' => '21:00',
            'personas' => 2,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/reservas/{$reserva->id}", [
            'fecha' => $this->fecha(),
            'hora' => '20:30',
            'personas' => 3,
        ]);

        $response->assertOk()->assertJsonPath('data.hora', '20:30');
        $this->assertDatabaseHas('reservas', ['id' => $reserva->id, 'personas' => 3, 'estado' => 'pendiente']);
    }

    public function test_editing_a_confirmed_reservation_resets_it_to_pending(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create([
            'user_id' => $user->id,
            'fecha' => $this->fecha(),
            'hora' => '21:00',
            'personas' => 2,
            'estado' => 'confirmada',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/reservas/{$reserva->id}", [
            'fecha' => $this->fecha(),
            'hora' => '21:30',
            'personas' => 2,
        ]);

        $response->assertOk()->assertJsonPath('data.estado', 'pendiente');
        $this->assertDatabaseHas('reserva_eventos', [
            'reserva_id' => $reserva->id,
            'estado_anterior' => 'confirmada',
            'estado_nuevo' => 'pendiente',
        ]);
    }

    public function test_user_cannot_edit_another_users_reservation(): void
    {
        $owner = User::factory()->create(['role' => 'client']);
        $intruder = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create([
            'user_id' => $owner->id,
            'fecha' => $this->fecha(),
            'hora' => '21:00',
            'personas' => 2,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($intruder)->patchJson("/api/reservas/{$reserva->id}", [
            'fecha' => $this->fecha(),
            'hora' => '20:30',
            'personas' => 2,
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_edit_cancelled_reservation(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $reserva = Reserva::factory()->create([
            'user_id' => $user->id,
            'fecha' => $this->fecha(),
            'hora' => '21:00',
            'personas' => 2,
            'estado' => 'cancelada',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/reservas/{$reserva->id}", [
            'fecha' => $this->fecha(),
            'hora' => '20:30',
            'personas' => 2,
        ]);

        $response->assertForbidden();
    }
}
