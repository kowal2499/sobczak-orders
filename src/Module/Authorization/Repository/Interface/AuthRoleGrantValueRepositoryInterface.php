<?php

namespace App\Module\Authorization\Repository\Interface;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;

interface AuthRoleGrantValueRepositoryInterface
{
    public function findOneByRoleAndGrant(AuthRole $authRole, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthRoleGrantValue;

    /**
     * @param AuthRole $authRole
     * @return AuthRoleGrantValue[]
     */
    public function findAllByRole(AuthRole $authRole): array;

    /**
     * @param AuthRoleGrantValue $authRoleGrantValue
     * @param bool $flush
     * @return void
     */
    public function add(AuthRoleGrantValue $authRoleGrantValue, bool $flush = true): void;


}