<?php

namespace App\Module\Authorization\Repository\Test;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;

class AuthUserGrantValueTestRepository implements AuthUserGrantValueRepositoryInterface
{

    public function findOneByUserAndGrant(User $user, AuthGrant $authGrant, ?string $grantOptionSlug = null): ?AuthUserGrantValue
    {
        return null;
    }

    public function findAllByUser(User $user): array
    {
        return [];
    }
}