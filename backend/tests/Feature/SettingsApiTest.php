<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_expose_expanded_subset(): void
    {
        Setting::query()->updateOrCreate(['id' => 1], [
            'nombre_restaurante' => 'Restaurante La Laguna',
            'apertura_comida' => '13:00',
            'cierre_comida' => '16:00',
            'apertura_cena' => '20:00',
            'cierre_cena' => '23:30',
            'duracion_turno' => 120,
            'ticket_medio' => 38,
            'intervalo_slots' => 30,
            'antelacion_min_horas' => 1,
            'max_personas_online' => 20,
            'dias_cierre' => [1],
            'email_contacto' => 'reservas@laguna.com',
            'telefono' => '+34 922 000 000',
            'direccion' => 'Calle La Carrera, 1',
            'lat' => 28.4874,
            'lng' => -16.3159,
            'galeria' => ['https://example.com/a.jpg'],
        ]);

        $response = $this->getJson('/api/settings');

        $response->assertOk()
            // Backwards-compatible keys still present.
            ->assertJsonPath('nombre_restaurante', 'Restaurante La Laguna')
            ->assertJsonStructure(['nombre_restaurante', 'horarios' => ['comida', 'cena']])
            // New v2 keys.
            ->assertJsonStructure([
                'horarios_detalle',
                'dias_cierre',
                'reservas' => ['intervalo_slots', 'antelacion_min_horas', 'max_personas_online', 'ventana_dias'],
                'branding' => ['logo_url', 'color_primario', 'color_acento'],
                'contacto' => ['email', 'telefono', 'direccion', 'ciudad'],
                'coords' => ['lat', 'lng'],
                'social' => ['instagram', 'facebook', 'tiktok'],
                'galeria',
            ])
            ->assertJsonPath('contacto.telefono', '+34 922 000 000')
            ->assertJsonPath('coords.lat', 28.4874);
    }

    public function test_admin_can_update_expanded_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings', [
            'nombre_restaurante' => 'Nuevo Nombre',
            'ticket_medio' => 42.5,
            'horarios_detalle' => [
                'apertura_comida' => '12:30',
                'cierre_comida' => '16:00',
                'apertura_cena' => '20:00',
                'cierre_cena' => '23:30',
            ],
            'reservas' => [
                'intervalo_slots' => 15,
                'antelacion_min_horas' => 2,
                'max_personas_online' => 12,
            ],
            'dias_cierre' => [0, 1],
            'branding' => ['color_primario' => '#abc123'],
            'contacto' => ['email' => 'hola@laguna.com'],
            'galeria' => ['https://example.com/x.jpg'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reservas.intervalo_slots', 15)
            ->assertJsonPath('data.dias_cierre', [0, 1])
            ->assertJsonPath('data.contacto.email', 'hola@laguna.com');
    }

    public function test_invalid_hex_color_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patchJson('/api/admin/settings', [
            'nombre_restaurante' => 'X',
            'ticket_medio' => 30,
            'horarios_detalle' => [
                'apertura_comida' => '13:00',
                'cierre_comida' => '16:00',
                'apertura_cena' => '20:00',
                'cierre_cena' => '23:30',
            ],
            'reservas' => [
                'intervalo_slots' => 30,
                'antelacion_min_horas' => 1,
                'max_personas_online' => 20,
            ],
            'branding' => ['color_primario' => 'not-a-color'],
        ])->assertStatus(422)->assertJsonValidationErrors('branding.color_primario');
    }
}
