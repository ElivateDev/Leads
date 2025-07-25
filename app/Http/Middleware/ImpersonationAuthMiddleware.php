<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Http\Middleware\Authenticate;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationAuthMiddleware extends Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        if (session('is_impersonating') && Auth::check()) {
            return $next($request);
        }
        return parent::handle($request, $next, ...$guards);
    }
}
