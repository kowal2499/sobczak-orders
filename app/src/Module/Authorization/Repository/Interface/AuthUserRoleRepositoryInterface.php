<?php

namespace App\Module\Authorization\Repository\Interface;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthUserRole;

interface AuthUserRoleRepositoryInterface
{
    public function add(AuthUserRole $userRole, bool $flush = true): void;
    public function findAllByUser(User $user): array;
}