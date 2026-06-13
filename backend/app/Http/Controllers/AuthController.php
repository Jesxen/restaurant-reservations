<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new client account and return an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->input('phone'),
            'password' => $request->string('password'),
            'role' => 'client',
        ]);

        // Send the verification email. We do NOT block login on verification
        // (portfolio app stays usable); the user resource exposes email_verified.
        $user->sendEmailVerificationNotification();

        return $this->tokenResponse($user, 201);
    }

    /**
     * Authenticate and return an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está desactivada.'],
            ]);
        }

        return $this->tokenResponse($user);
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], $status);
    }
}
