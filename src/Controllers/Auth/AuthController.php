<?php

namespace App\Controllers\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Restaurant;
use App\Services\Auth\JWTService;
use App\Core\Database;

class AuthController
{
    private JWTService $jwtService;
    private Response $response;

    public function __construct()
    {
        $this->jwtService = new JWTService();
        $this->response = new Response();
    }

    /**
     * User login
     */
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            return $this->response->error('Email and password are required', 422);
        }

        // Find user
        $user = User::findByEmail($email);

        if (!$user) {
            return $this->response->error('Invalid credentials', 401);
        }

        // Verify password
        if (!User::verifyPassword($user, $password)) {
            return $this->response->error('Invalid credentials', 401);
        }

        // Check if user is active
        if (!$user['is_active']) {
            return $this->response->error('Account is inactive', 403);
        }

        // Get user with role
        $user = User::findWithRole($user['id']);

        // Generate tokens
        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken();

        // Store refresh token
        $expiresAt = date('Y-m-d H:i:s', time() + $this->jwtService->getRefreshExpiration());
        Database::insert('refresh_tokens', [
            'user_id' => $user['id'],
            'token' => $refreshToken,
            'expires_at' => $expiresAt
        ]);

        // Update last login
        User::updateLastLogin($user['id']);

        // Remove password from response
        unset($user['password']);

        return $this->response->json([
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwtService->getExpiration()
        ], 200, 'Login successful');
    }

    /**
     * Register restaurant
     */
    public function register(Request $request)
    {
        $data = $request->only([
            'restaurant_name', 'email', 'phone', 'password',
            'password_confirmation', 'first_name', 'last_name',
            'currency', 'timezone'
        ]);

        // Validate
        if (!$data['restaurant_name'] || !$data['email'] || !$data['password']) {
            return $this->response->error('Missing required fields', 422);
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return $this->response->error('Password confirmation does not match', 422);
        }

        // Check if email already exists
        if (Restaurant::whereOne('email', $data['email'])) {
            return $this->response->error('Email already registered', 422);
        }

        Database::beginTransaction();

        try {
            // Create restaurant
            $slug = slugify($data['restaurant_name']);
            $restaurantId = Restaurant::create([
                'name' => $data['restaurant_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'timezone' => $data['timezone'] ?? 'UTC',
                'status' => 'pending'
            ]);

            // Create restaurant settings
            Database::insert('restaurant_settings', [
                'tenant_id' => $restaurantId
            ]);

            // Create owner user
            $userId = Database::insert('users', [
                'tenant_id' => $restaurantId,
                'role_id' => 2, // Restaurant Owner
                'first_name' => $data['first_name'] ?? 'Owner',
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Database::commit();

            // Get user with role
            $user = User::findWithRole($userId);

            // Generate tokens
            $accessToken = $this->jwtService->generateAccessToken($user);
            $refreshToken = $this->jwtService->generateRefreshToken();

            // Store refresh token
            $expiresAt = date('Y-m-d H:i:s', time() + $this->jwtService->getRefreshExpiration());
            Database::insert('refresh_tokens', [
                'user_id' => $userId,
                'token' => $refreshToken,
                'expires_at' => $expiresAt
            ]);

            unset($user['password']);

            return $this->response->json([
                'restaurant' => Restaurant::find($restaurantId),
                'user' => $user,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $this->jwtService->getExpiration()
            ], 201, 'Restaurant registered successfully');

        } catch (\Exception $e) {
            Database::rollback();
            logError('Registration failed: ' . $e->getMessage());
            return $this->response->error('Registration failed', 500);
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return $this->response->error('Refresh token required', 422);
        }

        // Find refresh token
        $tokenRecord = Database::queryOne(
            "SELECT * FROM refresh_tokens WHERE token = :token AND is_revoked = 0 AND expires_at > NOW()",
            ['token' => $refreshToken]
        );

        if (!$tokenRecord) {
            return $this->response->error('Invalid or expired refresh token', 401);
        }

        // Get user
        $user = User::findWithRole($tokenRecord['user_id']);

        if (!$user || !$user['is_active']) {
            return $this->response->error('User not found or inactive', 401);
        }

        // Generate new access token
        $accessToken = $this->jwtService->generateAccessToken($user);

        return $this->response->json([
            'access_token' => $accessToken,
            'expires_in' => $this->jwtService->getExpiration()
        ], 200, 'Token refreshed successfully');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        if ($refreshToken) {
            // Revoke refresh token
            Database::update(
                'refresh_tokens',
                ['is_revoked' => 1],
                'token = :token',
                ['token' => $refreshToken]
            );
        }

        return $this->response->json(null, 200, 'Logged out successfully');
    }

    /**
     * Get current user
     */
    public function me(Request $request)
    {
        $user = $request->user;
        unset($user['password']);

        return $this->response->json($user, 200);
    }
}
