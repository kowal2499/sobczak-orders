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
        if (!$this->findOneByName($role->getName())) {
            $this->rolesMap[$role->getName()] = $role;
        }
    }

    public function findOneByName(string $name): ?AuthRole
    {
        return $this->rolesMap[$name] ?? null;
    }
}