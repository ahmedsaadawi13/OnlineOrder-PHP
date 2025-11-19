<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\JWTService;
use App\Models\User;

class AuthMiddleware
{
    private JWTService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JWTService();
    }

    /**
     * Handle authentication
     */
    public function handle(Request $request): ?Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return (new Response())->error('Unauthorized. Token missing.', 401);
        }

        $payload = $this->jwtService->verifyToken($token);

        if (!$payload) {
            return (new Response())->error('Unauthorized. Invalid or expired token.', 401);
        }

        // Load full user from database
        $user = User::find($payload['sub']);

        if (!$user || !$user['is_active']) {
            return (new Response())->error('Unauthorized. User not found or inactive.', 401);
        }

        // Attach user to request
        $request->user = $user;
        $request->tenantId = $user['tenant_id'];

        return null; // Continue to next middleware/handler
    }
}
