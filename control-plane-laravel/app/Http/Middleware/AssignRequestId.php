<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID', (string) Str::uuid());
        
        // Bind to request for later use
        $request->attributes->set('request_id', $requestId);

        // Share with logging context
        Log::shareContext([
            'request_id' => $requestId,
            'user_id' => auth()->id() ?? 'guest',
        ]);

        $response = $next($request);

        // Add to response headers
        $response->headers->set('X-Request-ID', $requestId);

        // Log the structured request details
        Log::info('API Request Handled', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'duration' => defined('LARAVEL_START') ? round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms' : 'unknown',
        ]);

        return $response;
    }
}
