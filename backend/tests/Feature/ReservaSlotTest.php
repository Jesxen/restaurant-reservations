<?php

namespace Tests\Feature;

use App\Models\BlackoutDate;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservaSlotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic settings: 30-min slots, 1h lead, closed Mondays.
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
            'dias_cierre' => [1], // Monday
        ]);
    }

    /** Pick a near-future date that is NOT a Monday and NOT blacked out. */
    private function fechaAbierta(): string
    {
        $d = Carbon::today()->addDays(3);
        while ($d->dayOfWeek === Carbon::MONDAY) {
            $d->addDay();
        }

        return $d->toDateString();
    }

    public function test_reservation_is_created_successfully(): void
    {
        $fecha = $this->fechaAbierta();

        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'fecha' => $fecha,
            'hora' => '21:00',
            'personas' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente');

        $this->assertDatabaseHas('reservas', [
            'email' => 'juan@example.com',
            'fecha' => $fecha,
            'estado' => 'pendiente',
        ]);

        $reserva = Reserva::where('email', 'juan@example.com')->first();
        $this->assertSame('21:00', substr((string) $reserva->hora, 0, 5));
    }

    public function test_overlapping_reservations_are_rejected_when_capacity_is_full(): void
    {
        $fecha = $this->fechaAbierta();

        // Fill the slot at 21:00 (capacity 20). duracion_turno=120 so 21:30 overlaps.
        Reserva::factory()->create([
            'fecha' => $fecha,
            'hora' => '21:00',
            'personas' => 20,
            'estado' => 'confirmada',
        ]);

        // 21:30 overlaps [21:00, 23:00); should be rejected for overbooking.
        $response = $this->postJson('/api/reservas', [
            'nombre' => 'María',
            'email' => 'maria@example.com',
            'fecha' => $fecha,
            'hora' => '21:30',
            'personas' => 2,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('personas');
    }

    public function test_non_overlapping_slot_remains_available(): void
    {
        $fecha = $this->fechaAbierta();

        // Lunch fully booked at 13:00 (occupies [13:00, 15:00)).
        Reserva::factory()->create([
            'fecha' => $fecha,
            'hora' => '13:00',
            'personas' => 20,
            'estado' => 'confirmada',
        ]);

        // Dinner at 20:00 does not overlap lunch; should succeed.
        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'fecha' => $fecha,
            'hora' => '20:00',
            'personas' => 4,
        ]);

        $response->assertCreated();
    }

    public function test_closed_weekday_is_rejected(): void
    {
        // Next Monday.
        $lunes = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Pedro',
            'email' => 'pedro@example.com',
            'fecha' => $lunes,
            'hora' => '21:00',
            'personas' => 2,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('fecha');
    }

    public function test_blackout_date_is_rejected(): void
    {
        $fecha = $this->fechaAbierta();
        BlackoutDate::create(['fecha' => $fecha, 'motivo' => 'Evento privado']);

        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Lucía',
            'email' => 'lucia@example.com',
            'fecha' => $fecha,
            'hora' => '21:00',
            'personas' => 2,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('fecha');
    }

    public function test_invalid_slot_alignment_is_rejected(): void
    {
        $fecha = $this->fechaAbierta();

        // 21:15 is not on a 30-min grid.
        $response = $this->postJson('/api/reservas', [
            'nombre' => 'Sergio',
            'email' => 'sergio@example.com',
            'fecha' => $fecha,
            'hora' => '21:15',
            'personas' => 2,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('hora');
    }

    public function test_horarios_endpoint_returns_slots(): void
    {
        $fecha = $this->fechaAbierta();

        $response = $this->getJson('/api/horarios?fecha='.$fecha);

        $response->assertOk()
            ->assertJsonPath('abierto', true)
            ->assertJsonStructure([
                'fecha',
                'abierto',
                'motivo_cierre',
                'slots' => [['hora', 'disponible', 'plazas_disponibles']],
            ]);
    }

    public function test_horarios_endpoint_reports_closed_day(): void
    {
        $lunes = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        $response = $this->getJson('/api/horarios?fecha='.$lunes);

        $response->assertOk()
            ->assertJsonPath('abierto', false)
            ->assertJsonPath('slots', []);
    }
}
