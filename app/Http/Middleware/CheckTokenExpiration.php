<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();
            
            if ($token->expires_at && $token->expires_at->isPast()) {
                $token->delete();
                
                return response()->json([
                    'message' => 'Token has expired'
                ], 401);
            }
        }
        
        return $next($request);
    }
}
