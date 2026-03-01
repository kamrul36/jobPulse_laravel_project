<?php

namespace App\Http\Middleware;

use App\Helper\ResponseHelper;
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

        try {   
            $decoded = $jwtService->decodeToken(token: $token);

            $userId = $decoded->id ?? null;
            $userRole = $decoded->role ?? null;

            if (!$userId || !$userRole) {
                return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
            }

            if ($jwtService->isBlacklisted($token)) {
                return response()->json(['success' => false, 'message' => 'Token invalidated'], 401);
            }

            if (!in_array($userRole, ['admin', 'employer'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
            }

            // Attach data to request
            $request->merge([
                'auth_user_id' => $userId,
                'auth_user_role' => $userRole,
            ]);

            // Optional: Add a helper to get full user only when needed
            $request->macro('getAuthUser', function () use ($jwtService, $token) {
                return $jwtService->getUserFromToken($token);
            });

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }

        // $user = $jwtService->getUserFromToken($token);

        // if (!$user) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized'
        //     ], 401);
        // }

        // // Check if user has employer or admin role
        // if (!$user->hasRole(['admin', 'employer'])) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Admin & employers can create jobs'
        //     ], 403);
        // }

        // // Attach user to request (VERY IMPORTANT)
        // $request->merge(['auth_user' => $user]);

        // return $next($request);
    }
}
