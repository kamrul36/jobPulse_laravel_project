<?php

namespace App\Http\Middleware;

use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckJobPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        // Get authenticated user from JWT token
        $jwtService = new JWTService();
        $token = $jwtService->getTokenFromRequest();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided'
            ], 401);
        }

        $user = $jwtService->getUserFromToken($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Check if user has employer or admin role
        if (!$user->hasRole(['admin', 'employer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Admin & employers can create jobs'
            ], 403);
        }

        // Attach user to request (VERY IMPORTANT)
        $request->merge(['auth_user' => $user]);

        return $next($request);
    }
}
