<?php

namespace App\Helper;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class JWTHelper
{

  /**
     * Generate OTP code
     */
    public static function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

     /**
     * Send OTP via email
     */
    public static function sendOTPEmail(User $user, string $otp): bool
    {
        try {
            // In production, use proper mail service
            // For now, we'll just return true
            // Mail::to($user->email)->send(new OTPMail($otp));
            
            // Log OTP for development (remove in production)
            logger()->info("OTP for {$user->email}: {$otp}");
            
            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send OTP: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Send OTP via SMS
     */
    public static function sendOTPSMS(User $user, string $otp): bool
    {
        try {
            // Implement SMS service (Twilio, etc.)
            // For now, we'll just log it
            logger()->info("OTP for {$user->phone}: {$otp}");
            
            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send SMS OTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP
     */
    public static function verifyOTP(User $user, string $otp): bool
    {
        if (!$user->otp || !$user->otp_expires_at) {
            return false;
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return false;
        }

        return $user->otp === $otp;
    }

    /**
     * Clear OTP from user
     */
    public static function clearOTP(User $user): void
    {
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
        ]);
    }

    /**
     * Find user by username, email, or phone
     */
    public static function findUserByCredential(string $credential): ?User
    {
        return User::where('username', $credential)
            ->orWhere('email', $credential)
            ->orWhere('phone', $credential)
            ->first();
    }

    /**
     * Check if credential is email
     */
    public static function isEmail(string $credential): bool
    {
        return filter_var($credential, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if credential is phone
     */
    public static function isPhone(string $credential): bool
    {
        return preg_match('/^[+]?[0-9]{10,15}$/', $credential);
    }

    /**
     * Validate login credential
     */
    public static function validateLoginCredential(User $user, string $credential): bool
    {
        if (self::isEmail($credential)) {
            return $user->isEmailVerified();
        }

        if (self::isPhone($credential)) {
            return $user->isPhoneVerified();
        }

        // Username doesn't need verification
        return true;
    }

    /**
     * Generate password reset token
     */
    public static function generateResetToken(): string
    {
        return Str::random(64);
    }

}