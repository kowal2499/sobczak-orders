<?php

namespace App\Tests\Utilities\Cache;

use Symfony\Contracts\Cache\ItemInterface;

class TestCacheItem implements ItemInterface
{

    public function getKey()
    {
        // TODO: Implement getKey() method.
    }

    public function get()
    {
        // TODO: Implement get() method.
    }

    public function isHit()
    {
        // TODO: Implement isHit() method.
    }

    public function set($value)
    {
        // TODO: Implement set() method.
    }

    public function expiresAt($expiration)
    {
        // TODO: Implement expiresAt() method.
    }

    public function expiresAfter($time)
    {
        // TODO: Implement expiresAfter() method.
    }

    public function tag($tags): ItemInterface
    {
        return $this;
    }

    public function getMetadata(): array
    {
        return [];
    }
}