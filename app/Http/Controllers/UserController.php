<?php

namespace App\Http\Controllers;

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
                'username' => 'sometimes|string|unique:users,username,' . $user->id . '|min:3|max:50',
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

            // Update username
            if ($request->has('username')) {
                $updateData['username'] = $request->username;
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
}
