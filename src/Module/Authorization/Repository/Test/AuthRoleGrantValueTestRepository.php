<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;

class AuthRoleGrantValueTestRepository implements AuthRoleGrantValueRepositoryInterface
{
    private array $valuesMap = [];

    public function findOneByRoleAndGrant(AuthRole $authRole, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthRoleGrantValue
    {
        return null;
    }

    public function findAllByRole(AuthRole $authRole): array
    {
        return $this->valuesMap[$authRole->getId()] ?? [];
    }

    public function add(AuthRoleGrantValue $authRoleGrantValue, bool $flush = true): void
    {
        $roleId = $authRoleGrantValue->getRole()->getId();
        $grantFullSlug = $authRoleGrantValue->getGrantVO()->toString();
        $identifier = $roleId . '-' . $grantFullSlug;
        $this->valuesMap[$identifier] = $authRoleGrantValue;
        // todo
        // nie nie
    }
}