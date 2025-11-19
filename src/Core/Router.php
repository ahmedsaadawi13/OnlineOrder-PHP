<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $groupMiddleware = [];

    /**
     * Register GET route
     */
    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register PATCH route
     */
    public function patch(string $path, $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register route group with middleware
     */
    public function group(array $options, callable $callback): void
    {
        $previousMiddleware = $this->groupMiddleware;

        if (isset($options['middleware'])) {
            $this->groupMiddleware = array_merge(
                $this->groupMiddleware,
                (array) $options['middleware']
            );
        }

        $callback($this);

        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Add route to collection
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware
        ];
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);

            if ($params !== null) {
                // Set route parameters
                $request->setParams($params);

                // Run middleware
                foreach ($route['middleware'] as $middlewareName) {
                    $middleware = $this->resolveMiddleware($middlewareName);
                    $result = $middleware->handle($request);

                    if ($result !== null) {
                        return $result;
                    }
                }

                // Execute handler
                return $this->executeHandler($route['handler'], $request);
            }
        }

        // No route found
        return (new Response())->error('Route not found', 404);
    }

    /**
     * Match route pattern against URI
     */
    private function matchRoute(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    /**
     * Resolve middleware instance
     */
    private function resolveMiddleware(string $name)
    {
        $middlewareMap = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'tenant' => \App\Middleware\TenantMiddleware::class,
            'role' => \App\Middleware\RoleMiddleware::class,
            'cors' => \App\Middleware\CorsMiddleware::class,
            'ratelimit' => \App\Middleware\RateLimitMiddleware::class,
        ];

        $className = $middlewareMap[$name] ?? $name;

        return new $className();
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler, Request $request)
    {
        if (is_string($handler)) {
            // Format: "ControllerName@method"
            [$controller, $method] = explode('@', $handler);
            $controllerClass = "App\\Controllers\\$controller";

            if (!class_exists($controllerClass)) {
                return (new Response())->error('Controller not found', 500);
            }

            $controllerInstance = new $controllerClass();

            if (!method_exists($controllerInstance, $method)) {
                return (new Response())->error('Method not found', 500);
            }

            return $controllerInstance->$method($request);
        }

        if (is_callable($handler)) {
            return $handler($request);
        }

        return (new Response())->error('Invalid handler', 500);
    }
}
