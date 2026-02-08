<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $user = $jwtService->getUserFromToken($token);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
                'current_password' => 'sometimes|required_with:new_password|string',
                'new_password' => 'sometimes|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];

            // Username cannot be updated
            if ($request->has('username')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username cannot be updated'
                ], 400);
            }

            // Update email (requires re-verification)
            if ($request->has('email') && $request->email !== $user->email) {
                $updateData['email'] = $request->email;
                $updateData['email_verified_at'] = null;
            }

            // Update phone (requires re-verification)
            if ($request->has('phone') && $request->phone !== $user->phone) {
                $updateData['phone'] = $request->phone;
                $updateData['phone_verified_at'] = null;
            }

            // Update password
            if ($request->has('current_password') && $request->has('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 400);
                }
                $updateData['password'] = $request->new_password;
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified' => $user->isEmailVerified(),
                        'phone_verified' => $user->isPhoneVerified(),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Deactivate user account (soft delete)
     */
    public function deactivate(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();
            $user = $jwtService->getUserFromToken($token);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is incorrect'
                ], 400);
            }

            // Soft delete or deactivate
            $user->update(['is_active' => false]);
            $user->delete(); // This will soft delete

            return response()->json([
                'success' => true,
                'message' => 'Account deactivated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deactivation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate user account
     */
    public function reactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credential' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::withTrashed()
                ->where(function ($query) use ($request) {
                    $query->where('username', $request->credential)
                        ->orWhere('email', $request->credential)
                        ->orWhere('phone', $request->credential);
                })
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user->restore();
            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Account reactivated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reactivation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
