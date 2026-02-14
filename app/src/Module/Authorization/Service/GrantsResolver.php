<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthAbstractGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use Symfony\Component\Security\Core\Security;

class GrantsResolver
{
    const ADMIN_GRANT = 'authorization.admin';

    public function __construct(
        private readonly AuthRoleGrantValueRepositoryInterface $roleGrantValueRepository,
        private readonly AuthUserGrantValueRepositoryInterface $userGrantValueRepository,
        private readonly AuthUserRoleRepositoryInterface       $userRoleRepository,
        private readonly Security                              $security,
        private readonly AuthCacheService                      $authCacheService,
        private readonly RolesMerger                           $rolesMerger,
        private readonly GrantValueSupplier                    $grantValueSupplier,
    ) {
    }

    public function isGranted(string $grantName): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $grants = $this->getGrants($user);

        if (in_array(self::ADMIN_GRANT, $grants)) {
            return true;
        }

        return in_array($grantName, $grants);
    }

    public function getGrants(User $user): array
    {
        $cacheKey = $this->authCacheService->getGrantsCacheKey($user);

        return $this->authCacheService->get($cacheKey, function () use ($user) {

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

    protected function getRoleNames(User $user): array
    {
        $cacheKey = $this->authCacheService->getRolesCacheKey($user);

        return $this->authCacheService->get($cacheKey, function() use ($user) {
            return array_merge(
                $user->getRoles(),
                array_map(
                    fn (AuthUserRole $userRole) => $userRole->getRole()->getName(),
                    $this->userRoleRepository->findAllByUser($user)
                )
            );
        });
    }

    /**
     * @param AuthUserRole[] $userRoles
     * @return array
     */
    protected function getFromRoles(array $userRoles): array
    {
        $result = [];
        $values = $this->rolesMerger->merge(
            ...array_map(fn ($userRoles) => $userRoles->getRole(), $userRoles)
        );
        foreach ($values as $roleGrantValue) {
            $result[$roleGrantValue->getGrantVO()->toString()] = true;
        }

        return $result;
    }

    protected function getFromUser(User $user): array
    {
        $result = [];
        foreach ($this->userGrantValueRepository->findAllByUser($user) as $grantValue) {
            // keep both true and false values from user grants
            $result[$grantValue->getGrantVO()->toString()] = $this->grantValueSupplier->getValue($grantValue);
        }
        return $result;
    }

    protected function merge(...$grantsPool): array
    {
        $result = [];
        foreach ($grantsPool as $pool) {
            foreach ($pool as $grantSlug => $grantValue) {
                $boolValue = (bool) $grantValue;
                if (array_key_exists($grantSlug, $result)) {
                    $result[$grantSlug] = $result[$grantSlug] && $boolValue;
                } else {
                    $result[$grantSlug] = $boolValue;
                }
            }
        }
        return $result;
    }
}