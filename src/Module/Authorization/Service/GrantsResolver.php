<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthAbstractGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GrantsResolver
{
    public function __construct(
        private readonly AuthRoleGrantValueRepositoryInterface $roleGrantValueRepository,
        private readonly AuthUserGrantValueRepositoryInterface $userGrantValueRepository,
        private readonly AuthUserRoleRepositoryInterface       $userRoleRepository,
        private readonly Security                              $security,
        private readonly CacheInterface                        $cache,
    ) {
    }

    public function isGranted(string $grantName): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (in_array('ROLE_ADMINISTRATOR', $this->getRoleNames($user))){
            return true;
        }

        $grants = $this->getGrants($user);
        return in_array($grantName, $grants);
    }

    protected function getRoleNames(User $user): array
    {
        $cacheKey = 'user_role_names_' . $user->getId();

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter(3600);

            return array_map(
                fn (AuthUserRole $userRole) => $userRole->getRole()->getName(),
                $this->userRoleRepository->findAllByUser($user)
            );
        });
    }

    public function getGrants(User $user): array
    {
        $cacheKey = 'user_grant_names_' . $user->getId();

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter(3600);

            $userRoles = $this->userRoleRepository->findAllByUser($user);

            $grants = $this->merge(
                $this->getFromRoles($userRoles),
                $this->getFromUser($user)
            );

            // remove falsy grants
            $grants = array_filter($grants, fn($value) => $value);
            // get only slugs
            return array_keys($grants);
        });
    }

    /**
     * @param AuthUserRole[] $userRoles
     * @return array
     */
    protected function getFromRoles(array $userRoles): array
    {
        $result = [];
        foreach ($userRoles as $userRole) {
            $grantValues = $this->roleGrantValueRepository->findAllByRole($userRole->getRole());
            foreach ($grantValues as $grantValue) {
                $value = $this->getGrantValue($grantValue);
                $key = $grantValue->getGrantVO()->toString();
                if (isset($result[$key])) {
                    $result[$key] = $result[$key] && $value;
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    protected function getFromUser(User $user): array
    {
        $result = [];
        foreach ($this->userGrantValueRepository->findAllByUser($user) as $grantValue) {
            $key = $grantValue->getGrantVO()->toString();
            $result[$key] = $this->getGrantValue($grantValue);
        }
        return $result;
    }

    protected function merge(...$grantsPool): array
    {
        $result = [];
        foreach ($grantsPool as $pool) {
            foreach ($pool as $grantSlug => $grantValue) {
                $result[$grantSlug] = (bool) $grantValue;
            }
        }
        return $result;
    }

    protected function getGrantValue(AuthAbstractGrantValue $grantValue): bool
    {
        return $grantValue->getValue() && $grantValue->getGrant()->getModule()->isActive();
    }
}