<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;

class AuthRoleGrantValueTestRepository implements AuthRoleGrantValueRepositoryInterface
{
    /** @var AuthRoleGrantValue[]  */
    private array $storage = [];

    public function findOneByRoleAndGrant(AuthRole $authRole, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthRoleGrantValue
    {
        return null;
    }

    public function findAllByRole(AuthRole $authRole): array
    {
        return array_filter($this->storage, fn(AuthRoleGrantValue $item) => $item->getRole()->getId() === $authRole->getId());
    }

    public function add(AuthRoleGrantValue $authRoleGrantValue, bool $flush = true): void
    {
        if (!$this->innerFind($authRoleGrantValue)) {
            $this->storage[] = $authRoleGrantValue;
        }
    }

    private function innerFind(AuthRoleGrantValue $roleGrantValue): ?AuthRoleGrantValue
    {
        foreach ($this->storage as $rgValue) {
            if (
                $rgValue->getRole()->getId() === $roleGrantValue->getRole()->getId()
                && $rgValue->getGrant()->getId() === $roleGrantValue->getGrant()->getId()
                && $rgValue->getGrantOptionSlug() === $roleGrantValue->getGrantOptionSlug()
            ) {
                return $rgValue;
            }
        }
        return null;
    }
}