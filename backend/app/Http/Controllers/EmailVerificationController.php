<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Verify the user's email via the signed URL.
     *
     * GET /api/email/verify/{id}/{hash}  (signed)
     *
     * Note: this route is NOT behind auth:sanctum because the link is opened
     * from an email client without a bearer token. The signature + hash bind
     * the request to the user, so we resolve and verify manually.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'El enlace de verificación no es válido.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu correo ya estaba verificado.']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Tu correo ha sido verificado correctamente.']);
    }

    /**
     * Resend the verification email to the authenticated user.
     *
     * POST /api/email/verification-notification  (auth:sanctum, throttled)
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu correo ya está verificado.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Te hemos reenviado el correo de verificación.']);
    }
}
