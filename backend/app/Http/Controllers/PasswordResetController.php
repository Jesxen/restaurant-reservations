<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link (token) to the given email.
     *
     * Always returns a generic success message to avoid leaking which emails
     * are registered.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo existe, te hemos enviado instrucciones para restablecer tu contraseña.',
        ]);
    }

    /**
     * Reset the password using a valid token. Revokes existing Sanctum tokens
     * so any stolen session is invalidated.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'token.required' => 'El token de restablecimiento es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe contener al menos una letra.',
            'password.numbers' => 'La contraseña debe contener al menos un número.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // hashed cast applies
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate any existing API tokens.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['El token de restablecimiento no es válido o ha caducado.'],
            ]);
        }

        return response()->json([
            'message' => 'Tu contraseña ha sido restablecida. Ya puedes iniciar sesión.',
        ]);
    }
}
