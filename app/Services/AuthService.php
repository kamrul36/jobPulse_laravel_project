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

        // Use Eloquent model method for transaction
        $user = User::create([
            'username' => $dto->username,
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => Hash::make($dto->password),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        //  Create profile using relationship (cleaner)
        if ($dto->isCandidate) {
            $user->jobseekerProfile()->create([]);
        } else {
            $user->employerProfile()->create([]);
        }

        // Generate and send OTP
        $otp = JWTHelper::generateOTP();
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        JWTHelper::sendOTPEmail($user, $otp);

        return [
            'user' => $user->fresh(['role']),
            'role' => $dto->getRoleSlug(),
        ];
    }
}