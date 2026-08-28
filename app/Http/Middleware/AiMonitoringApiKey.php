<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AiMonitoringApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $provided = (string) $request->header('X-API-KEY', '');
        $expected = (string) config('ai_monitoring.api_key');

        if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
