<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    /**
     * Handle CORS
     */
    public function handle(Request $request): ?Response
    {
        // CORS is handled at application level
        // This middleware is just a placeholder
        return null;
    }
}
