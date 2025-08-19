<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Tests\Utilities\PrivateProperty;

class AuthRoleGrantValueTestRepository implements AuthRoleGrantValueRepositoryInterface
{
    /** @var AuthRoleGrantValue[]  */
    private array $storage = [];

    public function findOneByRoleAndGrant(AuthRole $authRole, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthRoleGrantValue
    {
        foreach ($this->storage as $rgValue) {
            if (
                $rgValue->getRole()->getId() === $authRole->getId()
                && $rgValue->getGrant()->getId() === $authGrant->getId()
                && $rgValue->getGrantOptionSlug() === $grantOptionSlug
            ) {
                return $rgValue;
            }
        }
        return null;
    }

    public function findAllByRole(AuthRole $authRole): array
    {
        return array_filter($this->storage, fn(AuthRoleGrantValue $item) => $item->getRole()->getId() === $authRole->getId());
    }

    public function add(AuthRoleGrantValue $authRoleGrantValue, bool $flush = true): void
    {
        if (!$this->findOneByRoleAndGrant($authRoleGrantValue->getRole(), $authRoleGrantValue->getGrant(), $authRoleGrantValue->getGrantOptionSlug())) {
            PrivateProperty::setId($authRoleGrantValue);
            $this->storage[] = $authRoleGrantValue;
        }
    }

}