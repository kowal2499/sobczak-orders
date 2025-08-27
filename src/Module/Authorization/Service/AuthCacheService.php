<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;

class AuthCacheService
{

    private CacheInterface $cache;
    private Security $security;

    public function __construct(CacheInterface $cache, Security $security)
    {
        $this->cache = $cache;
        $this->security = $security;
    }

    public function invalidate(): void
    {
        $this->cache->delete($this->getRolesCacheKey());
        $this->cache->delete($this->getGrantsCacheKey());
    }

    public function getRolesCacheKey(): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return 'user_role_names_' . $user->getId();
    }

    public function getGrantsCacheKey(): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return 'user_grant_names_' . $user->getId();
    }

    public function get(string $key, callable $callback)
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(3600);
            return $callback($item);
        });
    }
}