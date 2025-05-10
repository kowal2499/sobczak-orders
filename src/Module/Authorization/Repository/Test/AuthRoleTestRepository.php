<?php

namespace App\Module\Authorization\Repository\Test;

use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Repository\Interface\AuthRoleRepositoryInterface;

class AuthRoleTestRepository implements AuthRoleRepositoryInterface
{

    /** @var AuthRole[] $roles */
    private array $rolesMap = [];

    public function add(AuthRole $role, bool $flush = true): void
    {
        if (!$this->findById($role->getId())) {
            $this->rolesMap[$role->getId()] = $role;
        }
    }

    public function findOneByName(string $name): ?AuthRole
    {
        foreach ($this->rolesMap as $role) {
            if ($role->getName() === $name) {
                return $role;
            }
        }
        return null;
    }

    private function findById(int $id): ?AuthRole
    {
        return $this->rolesMap[$id] ?? null;
    }
}