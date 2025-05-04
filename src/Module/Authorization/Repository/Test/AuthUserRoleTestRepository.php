<?php

namespace App\Module\Authorization\Repository\Test;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;

class AuthUserRoleTestRepository implements AuthUserRoleRepositoryInterface
{
    private array $rolesMap = [];

    public function findAllByUser(User $user): array
    {
        return $this->rolesMap[$user->getId()] ?? [];
    }

    public function add(AuthUserRole $userRole, bool $flush = true): void
    {
        $roleNames = array_map(fn (AuthUserRole $ur) => $ur->getRole()->getName(), $this->findAllByUser($userRole->getUser()));
        if (in_array($userRole->getRole()->getName(), $roleNames)) {
            return;
        }
        $userId = $userRole->getUser()->getId();
        if (false === isset($this->rolesMap[$userId])) {
            $this->rolesMap[$userId] = [];
        }
        $this->rolesMap[$userId][] = $userRole;
    }
}