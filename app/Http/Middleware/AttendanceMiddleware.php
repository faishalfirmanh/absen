<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {

        $user = auth()->user();

        dd($user);
        // Jika belum login
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Silakan login terlebih dahulu.'
            ], 401);
        }

        // ← AUTOMATICALLY add employee_id from logged-in user
        $request->merge(['employee_id' => $request->user()->id]);
        $request->merge(['data_user' => $request->user()]);
        $request->merge(['location_id' => $request->user()->location]);

        return $next($request);
    }
}