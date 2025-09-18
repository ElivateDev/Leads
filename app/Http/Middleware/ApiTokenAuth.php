<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json(['error' => 'API token required'], 401);
        }

        $apiToken = ApiToken::where('token', hash('sha256', $token))->first();

        if (!$apiToken) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        if ($apiToken->isExpired()) {
            return response()->json(['error' => 'API token has expired'], 401);
        }

        // Mark token as used
        $apiToken->markAsUsed();

        // Set the authenticated user
        auth()->setUser($apiToken->user);

        return $next($request);
    }

    /**
     * Get the token from the request
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        if ($request->bearerToken()) {
            return $request->bearerToken();
        }

        // Check query parameter
        if ($request->has('api_token')) {
            return $request->get('api_token');
        }

        // Check X-API-Token header
        if ($request->hasHeader('X-API-Token')) {
            return $request->header('X-API-Token');
        }

        return null;
    }
}