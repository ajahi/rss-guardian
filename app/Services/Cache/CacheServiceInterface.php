<?php

namespace App\Services\Cache;

interface CacheServiceInterface
{
    public function get(string $key): ?string;
    public function set(string $key, string $data, int $ttl = 600): bool;
    public function has(string $key): bool;
}

