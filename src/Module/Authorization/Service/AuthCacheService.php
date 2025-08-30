<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

class AuthCacheService
{

    private CacheInterface $cache;
    private Security $security;
    private LoggerInterface $logger;
    private LockFactory $lockFactory;
    private const KEYS_TRACKING = 'auth_cache_keys_tracking';

    public function __construct(CacheInterface $cache, Security $security, LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->cache = $cache;
        $this->security = $security;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
    }

    public function invalidateAll(): void
    {
        $keys = $this->cache->get(self::KEYS_TRACKING, fn() => []);
        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
        $this->cache->delete(self::KEYS_TRACKING);
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
        return $this->cache->get($key, function (ItemInterface $item) use ($key, $callback) {
            $item->expiresAfter(3600);

            // track keys with locking
            $lock = $this->lockFactory->createLock(self::KEYS_TRACKING . '_lock');
            if ($lock->acquire(true)) { // blokada z czekaniem
                try {
                    // Pobierz aktualną kolekcję kluczy
                    $keys = $this->cache->get(self::KEYS_TRACKING, fn() => []);
                    if (!in_array($key, $keys, true)) {
                        $keys[] = $key;
                        // Zapisz zaktualizowaną kolekcję
                        $item = $this->cache->getItem(self::KEYS_TRACKING);
                        $item->set($keys);
                        $this->cache->save($item);
                    }
                } finally {
                    $lock->release();
                }
            }

            return $callback($item);
        });
    }
}