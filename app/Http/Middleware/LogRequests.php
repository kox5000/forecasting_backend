<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Illuminate\Support\Facades\Log::info('API Request', [
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);

        $response = $next($request);

        \Illuminate\Support\Facades\Log::info('API Response', [
            'user_id' => auth()->id(),
            'status' => $response->getStatusCode(),
            'timestamp' => now(),
        ]);

        return $response;
    }
}
