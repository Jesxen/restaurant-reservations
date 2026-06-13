<?php

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_shows_only_approved_reviews_with_aggregate(): void
    {
        Review::factory()->aprobada()->create(['rating' => 4, 'comentario' => 'Muy bien']);
        Review::factory()->aprobada()->create(['rating' => 2, 'comentario' => 'Regular']);
        Review::factory()->create(['rating' => 5, 'comentario' => 'Pendiente moderación']); // not approved

        $response = $this->getJson('/api/reviews');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.rating_medio', 3);

        // Comentario of unapproved review must not leak.
        $this->assertStringNotContainsString('Pendiente moderación', $response->getContent());
    }

    public function test_resumen_endpoint_returns_aggregate(): void
    {
        Review::factory()->aprobada()->create(['rating' => 5]);
        Review::factory()->aprobada()->create(['rating' => 3]);

        $this->getJson('/api/reviews/resumen')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.rating_medio', 4);
    }

    public function test_client_without_completed_reservation_cannot_review(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        Reserva::factory()->create(['user_id' => $client->id, 'estado' => 'confirmada']);

        // Eligibility is enforced in the FormRequest (422) before the policy.
        $this->actingAs($client)
            ->postJson('/api/reviews', ['rating' => 5, 'comentario' => 'Excelente sitio'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reserva');
    }

    public function test_guest_cannot_review(): void
    {
        $this->postJson('/api/reviews', ['rating' => 5, 'comentario' => 'Excelente'])
            ->assertUnauthorized();
    }

    public function test_eligible_client_can_submit_review_pending_moderation(): void
    {
        $client = User::factory()->create(['role' => 'client', 'name' => 'Lucía']);
        $reserva = Reserva::factory()->create(['user_id' => $client->id, 'estado' => 'completada']);

        $response = $this->actingAs($client)->postJson('/api/reviews', [
            'rating' => 5,
            'comentario' => 'Comida espectacular, repetiremos.',
        ]);

        $response->assertCreated()->assertJsonPath('data.nombre', 'Lucía');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $client->id,
            'reserva_id' => $reserva->id,
            'rating' => 5,
            'aprobada' => false,
        ]);
    }

    public function test_client_cannot_review_more_than_completed_reservations(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        Reserva::factory()->create(['user_id' => $client->id, 'estado' => 'completada']);

        $this->actingAs($client)->postJson('/api/reviews', [
            'rating' => 5, 'comentario' => 'Primera reseña excelente',
        ])->assertCreated();

        // Only one completed reservation, already reviewed → second is rejected.
        $this->actingAs($client)->postJson('/api/reviews', [
            'rating' => 4, 'comentario' => 'Segunda reseña no permitida',
        ])->assertStatus(422);
    }

    public function test_staff_can_moderate_and_delete_reviews(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $review = Review::factory()->create(['aprobada' => false]);

        // Approve.
        $this->actingAs($staff)
            ->patchJson("/api/admin/reviews/{$review->id}", ['aprobada' => true])
            ->assertOk()
            ->assertJsonPath('data.aprobada', true);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'aprobada' => true]);

        // Admin list shows all reviews + filter.
        $this->actingAs($staff)->getJson('/api/admin/reviews?aprobada=1')
            ->assertOk()->assertJsonCount(1, 'data');

        // Delete.
        $this->actingAs($staff)->deleteJson("/api/admin/reviews/{$review->id}")->assertOk();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_client_cannot_moderate_reviews(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $review = Review::factory()->create();

        $this->actingAs($client)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($client)
            ->patchJson("/api/admin/reviews/{$review->id}", ['aprobada' => true])
            ->assertForbidden();
    }
}
