<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/forgot-password', ['email' => 'reset@example.com']);

        $response->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_leak_unknown_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

        // Generic success, no notification.
        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset2@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'reset2@example.com',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'reset3@example.com']);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => 'reset3@example.com',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_is_throttled(): void
    {
        $user = User::factory()->create(['email' => 'throttle@example.com']);

        // throttle:auth = 6/min keyed by ip|email.
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/forgot-password', ['email' => 'throttle@example.com'])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => 'throttle@example.com'])
            ->assertStatus(429);
    }
}
