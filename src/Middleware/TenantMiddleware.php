<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class TenantMiddleware
{
    /**
     * Handle tenant isolation
     */
    public function handle(Request $request): ?Response
    {
        // Ensure user is authenticated first
        if (!$request->user) {
            return (new Response())->error('Unauthorized', 401);
        }

        // Tenant ID should be set by AuthMiddleware
        if (!$request->tenantId) {
            return (new Response())->error('Forbidden. No tenant context.', 403);
        }

        // Store tenant ID globally for database queries
        $_SESSION['tenant_id'] = $request->tenantId;

        return null; // Continue
    }
}
