<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class AdminInactivityTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (!$token || !method_exists($token, 'delete')) {
            return response()->json([
                'message' => 'Unauthenticated. Please log in again.',
            ], 401);
        }

        $lastActivity = $token->last_activity_at ?? $token->created_at ?? now();

        // Check whether the admin has been inactive for 30 minutes.
        if (Carbon::parse($lastActivity)->lt(now()->subMinutes(30))) {
            $token->delete();

            return response()->json([
                'message' => 'Your session expired due to inactivity. Please log in again.',
            ], 401);
        }

        // Do not update activity here.
        // Only the dedicated activity endpoint will update it.

        return $next($request);
    }
}
