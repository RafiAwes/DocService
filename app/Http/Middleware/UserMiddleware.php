<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in.',
            ], 401);
        }

        // 2. Check if the user has the correct 'user' role
        // (Assuming you have a 'role' column in your users table)
        $user = Auth::user();

        if ($user->role !== 'user') { 
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Users only.',
            ], 403);
        }

        return $next($request);
    }
}