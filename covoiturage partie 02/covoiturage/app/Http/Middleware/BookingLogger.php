<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingLogger
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('Booking Operation', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'trip_id' => $request->input('trip_id'),
            'seats_booked' => $request->input('seats_booked'),
            'status_code' => $response->status(),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString()
        ]);
        
        return $response;
    }
}