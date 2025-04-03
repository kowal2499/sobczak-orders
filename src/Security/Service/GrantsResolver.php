<?php

namespace App\Security\Service;

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
}