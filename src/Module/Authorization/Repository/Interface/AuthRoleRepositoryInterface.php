<?php

namespace App\Module\Authorization\Repository\Interface;

use App\Module\Authorization\Entity\AuthRole;

interface AuthRoleRepositoryInterface
{
    public function add(AuthRole $role, bool $flush = true): void;
    public function findOneByName(string $name): ?AuthRole;
}