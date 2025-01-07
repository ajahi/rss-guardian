<?php

namespace App\Services\Cache;

use App\Services\Cache\CacheServiceInterface;

class FileCacheService implements CacheServiceInterface
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(string $cacheDir, int $defaultTtl = 600)
    {
        $this->cacheDir = $cacheDir;
        $this->defaultTtl = $defaultTtl;
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key): ?string
    {
        $cacheFile = $this->getCacheFilePath($key);
        
        if ($this->has($key)) {
            return file_get_contents($cacheFile);
        }
        
        return null;
    }

    public function set(string $key, string $data, int $ttl = 600): bool
    {
        $cacheFile = $this->getCacheFilePath($key);
        return file_put_contents($cacheFile, $data) !== false;
    }

    public function has(string $key): bool
    {
        $cacheFile = $this->getCacheFilePath($key);
        return file_exists($cacheFile) && 
               (time() - filemtime($cacheFile)) < $this->defaultTtl;
    }

    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.cache';
    }
}