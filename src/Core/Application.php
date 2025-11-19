<?php

namespace App\Core;

class Application
{
    private string $basePath;
    private Router $router;
    private Request $request;
    private Response $response;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
    }

    /**
     * Get router instance
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Handle CORS preflight
            if ($this->request->method() === 'OPTIONS') {
                $this->handleCors();
                exit;
            }

            // Add CORS headers to all responses
            $this->response->header('Access-Control-Allow-Origin', $this->getAllowedOrigin());
            $this->response->header('Access-Control-Allow-Credentials', 'true');
            $this->response->header('Access-Control-Max-Age', '3600');

            // Dispatch request
            $this->router->dispatch($this->request);

        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle CORS preflight request
     */
    private function handleCors(): void
    {
        header('Access-Control-Allow-Origin: ' . $this->getAllowedOrigin());
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');
        http_response_code(200);
    }

    /**
     * Get allowed origin for CORS
     */
    private function getAllowedOrigin(): string
    {
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));

        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        return $allowedOrigins[0] ?? '*';
    }

    /**
     * Handle exceptions
     */
    private function handleException(\Throwable $e): void
    {
        logError($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (env('APP_DEBUG', false)) {
            $this->response->error(
                'Internal Server Error: ' . $e->getMessage(),
                500,
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            );
        } else {
            $this->response->error('Internal Server Error', 500);
        }
    }

    /**
     * Get base path
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
