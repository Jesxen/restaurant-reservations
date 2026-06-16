<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Attach a baseline set of hardening headers to every API response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Modern browsers ignore the legacy XSS auditor; 0 disables it to
            // avoid known side-channel issues. CSP is the real defence.
            'X-XSS-Protection' => '0',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            // Force HTTPS for a year (Railway terminates TLS). Harmless over http.
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
