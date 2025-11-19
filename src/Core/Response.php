<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body;

    /**
     * Set HTTP status code
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set response header
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Send JSON response
     */
    public function json($data, int $status = 200, string $message = null): void
    {
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c'),
                'version' => '1.0'
            ]
        ];

        http_response_code($status);
        header('Content-Type: application/json');

        // Send headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send error response
     */
    public function error(string $message, int $status = 400, array $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => date('c'),
                'version' => '1.0'
            ]
        ];

        http_response_code($status);
        header('Content-Type: application/json');

        // Send headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send HTML response
     */
    public function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');

        // Send headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $html;
        exit;
    }

    /**
     * Redirect to URL
     */
    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    /**
     * Send no content response
     */
    public function noContent(): void
    {
        http_response_code(204);
        exit;
    }
}
