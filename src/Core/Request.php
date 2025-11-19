<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $params = [];
    private array $query = [];
    private array $body = [];
    private array $headers = [];
    private array $files = [];
    public ?int $tenantId = null;
    public ?array $user = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->parseUri();
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->parseBody();
        $this->parseHeaders();
    }

    /**
     * Parse request URI
     */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return rawurldecode($uri);
    }

    /**
     * Parse request body
     */
    private function parseBody(): void
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $json = file_get_contents('php://input');
                $this->body = json_decode($json, true) ?? [];
            } else {
                $this->body = $_POST;
            }
        }
    }

    /**
     * Parse request headers
     */
    private function parseHeaders(): void
    {
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $this->headers[strtolower($header)] = $value;
            }
        }

        // Special headers
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $this->headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
    }

    /**
     * Get HTTP method
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get route parameter
     */
    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Set route parameters
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get query parameter
     */
    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Get body parameter
     */
    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    /**
     * Get header
     */
    public function header(string $key, $default = null)
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * Get bearer token from header
     */
    public function bearerToken(): ?string
    {
        $authorization = $this->header('authorization');

        if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get uploaded file
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get all input (query + body)
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Check if key exists in input
     */
    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    /**
     * Get only specified keys from input
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
