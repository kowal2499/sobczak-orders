<?php

namespace App\Module\Authorization\Service;

use App\Entity\User;

class GrantsResolver
{
    public function __construct(
    ) {
    }

    public function resolve(User $user): array
    {
        return ['id' => $user->getId(), 'email' => $user->getEmail()];
    }

    public function getGlobalGrants(): array
    {
        return [];
    }

    public function getLocalGrants(): array
    {
        return [];
    }

    public function mergeGrants($globalGrants, $localGrants): array
    {
        return $globalGrants;
    }
}