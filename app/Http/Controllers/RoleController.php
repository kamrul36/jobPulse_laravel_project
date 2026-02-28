<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Models\Role;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Get all roles
     */
    public function index(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $currentUser = $jwtService->getUserFromToken($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (!$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super admin access required.'
                ], 403);
            }

            $roles = Role::all();

            return response()->json([
                'success' => true,
                'data' => ['roles' => $roles]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role (Super Admin only)
     */
    public function updateUserRole(Request $request, $userId)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $currentUser = $jwtService->getUserFromToken($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (!$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super admin access required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent changing own role
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own role'
                ], 400);
            }

            $user->update(['role_id' => $request->role_id]);

            $role = Role::find($request->role_id);

            return ResponseHelper::respond(
                'v1',
                'User role updated successfully',
                'PUT',
                200
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new role (Super Admin only)
     */
    public function create(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $currentUser = $jwtService->getUserFromToken($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (!$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super admin access required.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:roles',
                'slug' => 'required|string|unique:roles|regex:/^[a-z_]+$/',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = Role::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => ['role' => $role]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users with their roles (Admin & Super Admin)
     */
    public function getUsers(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $currentUser = $jwtService->getUserFromToken($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (!$currentUser->hasRole('super_admin') && !$currentUser->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $users = User::with('role')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'user_role' => $user->role->name,
                    'is_active' => $user->is_active,
                    'is_email_verified' => $user->isEmailVerified(),
                    'is_phone_verified' => $user->isPhoneVerified(),
                ];
            });

            return ResponseHelper::respond(
                'v1',
                'Get Users',
                'GET',
                200,
                $users,

            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
