<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
    private string $secret;
    private int $expiration;
    private int $refreshExpiration;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'change-this-in-production');
        $this->expiration = (int) env('JWT_EXPIRATION', 900); // 15 minutes
        $this->refreshExpiration = (int) env('JWT_REFRESH_EXPIRATION', 604800); // 7 days
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(array $user): string
    {
        $payload = [
            'sub' => $user['id'],
            'tenant_id' => $user['tenant_id'] ?? null,
            'email' => $user['email'],
            'role_id' => $user['role_id'] ?? null,
            'role' => $user['role'] ?? null,
            'iat' => time(),
            'exp' => time() + $this->expiration
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Decode and verify JWT token
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            logError('JWT verification failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get expiration time
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * Get refresh token expiration
     */
    public function getRefreshExpiration(): int
    {
        return $this->refreshExpiration;
    }
}
