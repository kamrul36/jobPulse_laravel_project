<?php

namespace App\Http\Controllers;

use App\Helper\JWTHelper;
use App\Models\Employer;
use App\Models\Jobseeker;
use App\Models\Role;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users|min:3|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|unique:users',
            'password' => 'required|string|min:8',
            'candidate' => 'required|boolean',
            'employer' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        // Ensure exactly one role is true
        if ($request->candidate === $request->employer) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly one role must be selected'
            ], 400);
        }

        try {
            $roleSlug = $request->candidate ? 'candidate' : 'employer';

            $role = Role::where('slug', $roleSlug)->firstOrFail();

            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'role_id' => $role->id,
                'is_active' => true,
            ]);

            if ($request->candidate) {
                Jobseeker::create(['user_id' => $user->id]);
            } else {
                Employer::create(['user_id' => $user->id]);
            }

            $otp = JWTHelper::generateOTP();

            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(2),
            ]);

            JWTHelper::sendOTPEmail($user, $otp);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email.',
                'data' => [
                    'id' => $user->id,
                    // 'username' => $user->username
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //register response

    // {"version": "v6",
    //      "success": true, 
    //      "method": "POST", 
    //      "operation": "Register new user.",
    //       "remaining_time": 86402, 
    //       "verification_email_code_dev_only": "752162", 
    //       "verification_phone_code_dev_only": "142239",
    //        "verification_id": "7ece8c9e-051c-11f1-890c-3db743ab6e34"}


    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credential' => 'required|string', // username, email, or phone
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = JWTHelper::findUserByCredential($request->credential);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is active
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ], 403);
            }

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Validate credential verification
            if (!JWTHelper::validateLoginCredential($user, $request->credential)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email or phone number first'
                ], 403);
            }

            // Generate JWT token
            $jwtService = new JWTService();
            $token = $jwtService->generateToken($user);

            return response()->json([
                'success' => true,
                'method' => 'POST',
                'operation' => 'Login',
                'message' => 'Login successful',
                "id" => $user->id,
                'expires_in' => $jwtService->getTTL(),
                'at' => $token
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //  log in response:
    // {
    //     "version": "v6", 
    //     "success": true,
    //      "method": "POST", 
    //      "operation": "Login.", 
    //      "expires_in": 360000, 
    //      "at": ""}


    /**
     * Request OTP for email/phone verification or password reset
     */
    public function requestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credential' => 'required|string',
            'type' => 'required|in:email_verification,phone_verification,password_reset',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = JWTHelper::findUserByCredential($request->credential);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $otp = JWTHelper::generateOTP();
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Send OTP based on type
            if ($request->type === 'phone_verification' && $user->phone) {
                JWTHelper::sendOTPSMS($user, $otp);
                $message = 'OTP sent to your phone number';
            } else {
                JWTHelper::sendOTPEmail($user, $otp);
                $message = 'OTP sent to your email';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email with OTP
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!JWTHelper::verifyOTP($user, $request->otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            $user->update([
                'email_verified_at' => Carbon::now(),
            ]);

            JWTHelper::clearOTP($user);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify phone with OTP
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!JWTHelper::verifyOTP($user, $request->otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            $user->update([
                'phone_verified_at' => Carbon::now(),
            ]);

            JWTHelper::clearOTP($user);

            return response()->json([
                'success' => true,
                'message' => 'Phone verified successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using OTP
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credential' => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = JWTHelper::findUserByCredential($request->credential);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!JWTHelper::verifyOTP($user, $request->otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            $user->update([
                'password' => $request->password,
            ]);

            JWTHelper::clearOTP($user);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();

            if ($token) {
                $jwtService->blacklistToken($token);
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        try {
            $jwtService = new JWTService();
            $oldToken = $jwtService->getTokenFromRequest();

            if (!$oldToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided'
                ], 401);
            }

            $newToken = $jwtService->refreshToken($oldToken);

            return response()->json([
                'success' => true,
                'method' => 'GET',
                'operation' => 'Get AT',
                'expires_in' => $jwtService->getTTL(),
                'at' => $newToken
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        try {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified' => $user->isEmailVerified(),
                        'phone_verified' => $user->isPhoneVerified(),
                        'role' => $user->role->name,
                        'is_active' => $user->is_active,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'error' => $e->getMessage()
            ], 401);
        }
    }


}
