<?php

/**
 * Global Helper Functions
 * These functions are available throughout the application
 */

if (!function_exists('env')) {
    /**
     * Get environment variable value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Handle boolean values
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        static $config = [];

        if (empty($config)) {
            // Load all config files
            $configPath = __DIR__ . '/../../config/';
            foreach (glob($configPath . '*.php') as $file) {
                $name = basename($file, '.php');
                $config[$name] = require $file;
            }
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('response')) {
    /**
     * Create JSON response
     *
     * @param mixed $data
     * @param int $status
     * @param string|null $message
     * @return void
     */
    function response($data = null, int $status = 200, string $message = null): void
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
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('responseError')) {
    /**
     * Create error response
     *
     * @param string $message
     * @param int $status
     * @param array|null $errors
     * @return void
     */
    function responseError(string $message, int $status = 400, array $errors = null): void
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
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die (for debugging)
     *
     * @param mixed ...$vars
     * @return void
     */
    function dd(...$vars): void
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}

if (!function_exists('slugify')) {
    /**
     * Convert string to URL-friendly slug
     *
     * @param string $text
     * @return string
     */
    function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'n-a' : $text;
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize input data
     *
     * @param mixed $data
     * @return mixed
     */
    function sanitize($data)
    {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('now')) {
    /**
     * Get current timestamp in MySQL format
     *
     * @return string
     */
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('generateToken')) {
    /**
     * Generate random token
     *
     * @param int $length
     * @return string
     */
    function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('formatMoney')) {
    /**
     * Format number as money
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function formatMoney(float $amount, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'SAR ',
            'AED' => 'AED ',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('validateEmail')) {
    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    /**
     * Validate phone number (basic validation)
     *
     * @param string $phone
     * @return bool
     */
    function validatePhone(string $phone): bool
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Check if length is between 10 and 15 digits
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
}

if (!function_exists('uploadFile')) {
    /**
     * Handle file upload
     *
     * @param array $file $_FILES array element
     * @param string $directory
     * @param array $allowedTypes
     * @return string|false File path or false on failure
     */
    function uploadFile(array $file, string $directory = 'uploads', array $allowedTypes = [])
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Validate file type if specified
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                return false;
            }
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;

        // Create directory if not exists
        $targetDir = __DIR__ . '/../../public/uploads/' . $directory;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '/uploads/' . $directory . '/' . $filename;
        }

        return false;
    }
}

if (!function_exists('deleteFile')) {
    /**
     * Delete uploaded file
     *
     * @param string $path
     * @return bool
     */
    function deleteFile(string $path): bool
    {
        $fullPath = __DIR__ . '/../../public' . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}

if (!function_exists('generateOrderNumber')) {
    /**
     * Generate unique order number
     *
     * @return string
     */
    function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}

if (!function_exists('calculateDistance')) {
    /**
     * Calculate distance between two coordinates (Haversine formula)
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

if (!function_exists('isWithinRadius')) {
    /**
     * Check if coordinates are within radius
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @param float $radius Radius in kilometers
     * @return bool
     */
    function isWithinRadius(float $lat1, float $lon1, float $lat2, float $lon2, float $radius): bool
    {
        $distance = calculateDistance($lat1, $lon1, $lat2, $lon2);
        return $distance <= $radius;
    }
}

if (!function_exists('logError')) {
    /**
     * Log error to file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function logError(string $message, array $context = []): void
    {
        $logFile = __DIR__ . '/../../storage/logs/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] ERROR: {$message}{$contextStr}\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('logInfo')) {
    /**
     * Log info to file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function logInfo(string $message, array $context = []): void
    {
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] INFO: {$message}{$contextStr}\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('trans')) {
    /**
     * Translate text (basic implementation)
     *
     * @param string $key
     * @param string $locale
     * @return string
     */
    function trans(string $key, string $locale = 'en'): string
    {
        // This is a basic implementation
        // In production, load translations from files
        static $translations = [];

        if (empty($translations)) {
            // Load translation files here
            // For now, return the key itself
        }

        return $translations[$locale][$key] ?? $key;
    }
}
