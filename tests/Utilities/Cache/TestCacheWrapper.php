<?php

namespace App\Tests\Utilities\Cache;

use Symfony\Contracts\Cache\CacheInterface;

class TestCacheWrapper implements CacheInterface
{

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null)
    {
        return $callback(new TestCacheItem());
    }

    public function delete(string $key): bool
    {
        return true;
    }
}