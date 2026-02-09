<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Cache;

class JWTService
{
    private string $secret;
    private string $algo = 'HS256';
    private int $ttl = 3600; // 1 hour in seconds
    private int $refreshTtl = 1209600; // 2 weeks in seconds

    public function __construct()
    {
        $this->secret = config('app.jwt_secret', env('JWT_SECRET'));
        $this->ttl = (int) env('JWT_TTL', 60) * 60; // Convert minutes to seconds
        $this->refreshTtl = (int) env('JWT_REFRESH_TTL', 20160) * 60; // Convert minutes to seconds
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken(User $user): string
    {
        $payload = [
            'sub' => $user->username,
            'id' => $user->id,
            'role' => $user->role->slug,
            'iss' => config('app.url'),
            'iat' => time(),
            'exp' => time() + $this->ttl,
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Decode and validate JWT token
     */
    public function decodeToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
        } catch (\Exception $e) {
            throw new \Exception('Invalid token');
        }
    }

    /**
     * Get user from token
     */
    public function getUserFromToken(string $token): ?User
    {
        try {
            $decoded = $this->decodeToken($token);

            // Check if token is blacklisted
            if ($this->isBlacklisted($token)) {
                return null;
            }

            return User::find($decoded->id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(string $token): string
    {
        try {
            $decoded = $this->decodeToken($token);
            $user = User::find($decoded->id);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Blacklist old token
            $this->blacklistToken($token);

            // Generate new token
            return $this->generateToken($user);
        } catch (\Exception $e) {
            throw new \Exception('Cannot refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Blacklist a token
     */
    public function blacklistToken(string $token): void
    {
        try {
            $decoded = $this->decodeToken($token);
            $expiresAt = $decoded->exp - time();

            if ($expiresAt > 0) {
                Cache::put('blacklist:' . $token, true, $expiresAt);
            }
        } catch (\Exception $e) {
            // Token already expired or invalid
        }
    }

    /**
     * Check if token is blacklisted
     */
    public function isBlacklisted(string $token): bool
    {
        return Cache::has('blacklist:' . $token);
    }

    /**
     * Get TTL in seconds
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

    /**
     * Extract token from request header
     */
    public function getTokenFromRequest(): ?string
    {
        $header = request()->header('Authorization', '');

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}