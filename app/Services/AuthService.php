<?php

namespace App\Services;

use App\DTOs\Auth\RegisterDTO;
use App\Helper\JWTHelper;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(RegisterDTO $dto): array
    {
        // Validate role selection
        if (!$dto->isValidRoleSelection()) {
            throw new \Exception('Exactly one role must be selected');
        }

        // Get role
        $role = Role::where('slug', $dto->getRoleSlug())->firstOrFail();

        // Generate OTPs
        $emailOtp = JWTHelper::generateOTP();
        $phoneOtp = $dto->phone ? JWTHelper::generateOTP() : null;


        $user = User::create([
            'username' => $dto->username,
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => Hash::make($dto->password),
            'role_id' => $role->id,
            'is_active' => false,
            'otp' => $emailOtp . ($phoneOtp ? '|' . $phoneOtp : ''), // Store both OTPs
            'otp_expires_at' => now()->addMinutes(5),
        ]);


        // Send OTPs
        JWTHelper::sendOTPEmail((object) $user, $emailOtp);

        if ($dto->phone && $phoneOtp) {
            JWTHelper::sendOTPSMS((object) $user, $phoneOtp);
        }

        // Return response with OTPs (dev only)
        return [
            'user_id' => $user->id,
            'email_otp' => config('app.env') === 'local' ? $emailOtp : null,
            'phone_otp' => config('app.env') === 'local' ? $phoneOtp : null,
            'expires_at' => $user->otp_expires_at,
        ];


        // //  Create profile using relationship (cleaner)
        // if ($dto->isCandidate) {
        //     $user->jobseekerProfile()->create([]);
        // } else {
        //     $user->employerProfile()->create([]);
        // }

        // // Generate and send OTP
        // $otp = JWTHelper::generateOTP();
        // $user->update([
        //     'otp' => $otp,
        //     'otp_expires_at' => now()->addMinutes(10),
        // ]);

        // JWTHelper::sendOTPEmail($user, $otp);

        // return [
        //     'user' => $user->fresh(['role']),
        //     'role' => $dto->getRoleSlug(),
        // ];
    }

    /* Verify OTP and activate user  */
    public function verifyRegistration(string $userId, string $otp): array
    {
        // Find user
        $user = User::find($userId);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check if already active
        if ($user->is_active) {
            throw new \Exception('User is already verified');
        }

        // Check OTP expiration
        if (now()->greaterThan($user->otp_expires_at)) {
            throw new \Exception('OTP has expired');
        }

        // Parse stored OTPs
        $otps = explode('|', $user->otp);
        $storedEmailOtp = $otps[0] ?? null;
        $storedPhoneOtp = $otps[1] ?? null;

        // Check if provided OTP matches either email or phone OTP
        $isEmailOtpValid = $storedEmailOtp === $otp;
        $isPhoneOtpValid = $storedPhoneOtp && $storedPhoneOtp === $otp;

        if (!$isEmailOtpValid && !$isPhoneOtpValid) {
            throw new \Exception('Invalid OTP');
        }

        // Determine which OTP was verified
        $emailVerified = $isEmailOtpValid;
        $phoneVerified = $isPhoneOtpValid;

        // Activate user and mark as verified
        $user->update([
            'is_active' => true,
            'email_verified_at' => $emailVerified ? now() : null,
            'phone_verified_at' => $phoneVerified ? now() : null,
            'otp' => null, // Clear OTP
            'otp_expires_at' => null,
        ]);

        // Create profile based on role
        if ($user->hasRole('candidate')) {
            $user->jobseekerProfile()->create([]);
        } else {
            $user->employerProfile()->create([]);
        }

        return [
            'user' => $user->fresh(['role']),
            'role' => $user->role->slug,
        ];
    }
}