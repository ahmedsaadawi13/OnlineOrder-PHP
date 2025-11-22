<?php

namespace App\Services;

class CacheService
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        $this->defaultTtl = 3600; // 1 hour

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached value
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!$data || !isset($data['expires_at']) || !isset($data['value'])) {
            return $default;
        }

        if (time() >= $data['expires_at']) {
            $this->forget($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Store value in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $file = $this->getCacheFile($key);

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        return file_put_contents($file, json_encode($data)) !== false;
    }

    /**
     * Store value permanently (no expiration)
     */
    public function forever(string $key, $value): bool
    {
        return $this->put($key, $value, 315360000); // 10 years
    }

    /**
     * Remember value with callback
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Remove value from cache
     */
    public function forget(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * Clear all cache
     */
    public function flush(): void
    {
        $files = glob($this->cacheDir . 'cache_*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get cache file path
     */
    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . 'cache_' . md5($key);
    }

    /**
     * Clean up expired cache files
     */
    public function cleanup(): void
    {
        $files = glob($this->cacheDir . 'cache_*');
        $now = time();

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $data = json_decode(file_get_contents($file), true);

            if (!$data || !isset($data['expires_at'])) {
                continue;
            }

            if ($now >= $data['expires_at']) {
                unlink($file);
            }
        }
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        $files = glob($this->cacheDir . 'cache_*');
        $totalSize = 0;
        $expired = 0;
        $active = 0;
        $now = time();

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $totalSize += filesize($file);
            $data = json_decode(file_get_contents($file), true);

            if ($data && isset($data['expires_at'])) {
                if ($now >= $data['expires_at']) {
                    $expired++;
                } else {
                    $active++;
                }
            }
        }

        return [
            'total_entries' => count($files),
            'active_entries' => $active,
            'expired_entries' => $expired,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
}
