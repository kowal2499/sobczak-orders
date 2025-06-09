<?php

namespace App\Module\Authorization\Repository\Test;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use App\Tests\Utilities\PrivateProperty;

class AuthUserRoleTestRepository implements AuthUserRoleRepositoryInterface
{
    private array $storage = [];

    public function findAllByUser(User $user): array
    {
        return $this->find($user, null);
    }

    public function add(AuthUserRole $userRole, bool $flush = true): void
    {
        if ($this->find($userRole->getUser(), $userRole->getRole())) {
            return;
        }
        PrivateProperty::setId($userRole);
        $this->storage[] = $userRole;
    }

    private function find(?User $user, ?AuthRole $role): array
    {
        return array_filter(
            array_map(
                fn(AuthUserRole $auRole) => (
                    ((!$user || $auRole->getUser()->getId() === $user->getId()) && (!$role || $auRole->getRole()->getId() === $role->getId()))
                    ? $auRole
                    : false
                ),
                $this->storage
            )
        );
    }
}